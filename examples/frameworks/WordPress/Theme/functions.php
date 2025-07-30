<?php

/**
 * WordPress Theme Integration with AI Rate Limiter
 * 
 * This file demonstrates how to integrate the AI Rate Limiter
 * into a WordPress theme for protecting API endpoints and forms.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Autoload the AI Rate Limiter library
require_once get_template_directory() . '/vendor/autoload.php';

use AhurSystem\AIRateLimiter\AIRateLimiter;

/**
 * Initialize AI Rate Limiter
 */
function wp_ai_rate_limiter_init() {
    global $wp_ai_limiter;
    
    try {
        $redis = new Redis();
        $redis->connect(
            defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1',
            defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379
        );
        
        $wp_ai_limiter = new AIRateLimiter($redis, [
            'default_limit' => get_option('wp_ai_limiter_default_limit', 100),
            'default_window' => get_option('wp_ai_limiter_default_window', 3600),
            'retry_strategy' => get_option('wp_ai_limiter_strategy', 'exponential'),
            'learning_enabled' => get_option('wp_ai_limiter_learning', true),
            'adaptive_throttling' => get_option('wp_ai_limiter_adaptive', true),
            'isolation_prefix' => 'wp_ai_limiter:'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('AI Rate Limiter initialization failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check rate limit for current request
 */
function wp_ai_rate_limiter_check($endpoint = 'default', $context = []) {
    global $wp_ai_limiter;
    
    if (!$wp_ai_limiter) {
        return null;
    }
    
    // Get identifier
    $identifier = wp_ai_rate_limiter_get_identifier();
    
    // Add WordPress context
    $context = array_merge($context, [
        'user_id' => get_current_user_id(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => wp_ai_rate_limiter_get_client_ip(),
        'is_logged_in' => is_user_logged_in(),
        'user_role' => wp_ai_rate_limiter_get_user_role()
    ]);
    
    return $wp_ai_limiter->check($identifier, $endpoint, $context);
}

/**
 * Get identifier for rate limiting
 */
function wp_ai_rate_limiter_get_identifier() {
    // Try to get user ID first
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }
    
    // Try to get API key from header
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        return 'api_' . sanitize_text_field($_SERVER['HTTP_X_API_KEY']);
    }
    
    // Fallback to IP address
    return 'ip_' . wp_ai_rate_limiter_get_client_ip();
}

/**
 * Get client IP address
 */
function wp_ai_rate_limiter_get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Get user role
 */
function wp_ai_rate_limiter_get_user_role() {
    if (!is_user_logged_in()) {
        return 'guest';
    }
    
    $user = wp_get_current_user();
    return !empty($user->roles) ? $user->roles[0] : 'subscriber';
}

/**
 * Protect AJAX endpoints
 */
function wp_ai_rate_limiter_protect_ajax() {
    // Skip for admin users
    if (current_user_can('manage_options')) {
        return;
    }
    
    $endpoint = $_POST['action'] ?? $_GET['action'] ?? 'default';
    $result = wp_ai_rate_limiter_check($endpoint);
    
    if ($result && !$result->isAllowed()) {
        wp_die(json_encode([
            'error' => 'Rate limit exceeded',
            'retry_after' => $result->getRetryDelay(),
            'reset_time' => $result->getResetDateTime()->format('Y-m-d H:i:s')
        ]), 'Rate Limit Exceeded', ['response' => 429]);
    }
}

/**
 * Protect REST API endpoints
 */
function wp_ai_rate_limiter_protect_rest($response, $handler, $request) {
    // Skip for admin users
    if (current_user_can('manage_options')) {
        return $response;
    }
    
    $endpoint = $request->get_route();
    $result = wp_ai_rate_limiter_check($endpoint);
    
    if ($result && !$result->isAllowed()) {
        return new WP_Error(
            'rate_limit_exceeded',
            'Rate limit exceeded',
            [
                'status' => 429,
                'retry_after' => $result->getRetryDelay(),
                'reset_time' => $result->getResetDateTime()->format('Y-m-d H:i:s')
            ]
        );
    }
    
    return $response;
}

/**
 * Protect comment form
 */
function wp_ai_rate_limiter_protect_comments() {
    if (current_user_can('moderate_comments')) {
        return;
    }
    
    $result = wp_ai_rate_limiter_check('comments');
    
    if ($result && !$result->isAllowed()) {
        wp_die(
            'Rate limit exceeded. Please try again later.',
            'Rate Limit Exceeded',
            ['response' => 429]
        );
    }
}

/**
 * Add rate limit headers to responses
 */
function wp_ai_rate_limiter_add_headers() {
    if (!is_admin()) {
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'default';
        $result = wp_ai_rate_limiter_check($endpoint);
        
        if ($result) {
            foreach ($result->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }
    }
}

/**
 * Initialize hooks
 */
function wp_ai_rate_limiter_init_hooks() {
    if (wp_ai_rate_limiter_init()) {
        // Protect AJAX requests
        add_action('wp_ajax_nopriv_', 'wp_ai_rate_limiter_protect_ajax', 1);
        add_action('wp_ajax_', 'wp_ai_rate_limiter_protect_ajax', 1);
        
        // Protect REST API
        add_filter('rest_pre_dispatch', 'wp_ai_rate_limiter_protect_rest', 10, 3);
        
        // Protect comment form
        add_action('pre_comment_on_post', 'wp_ai_rate_limiter_protect_comments');
        
        // Add headers
        add_action('send_headers', 'wp_ai_rate_limiter_add_headers');
    }
}

// Initialize on WordPress load
add_action('init', 'wp_ai_rate_limiter_init_hooks');

/**
 * Admin settings page
 */
function wp_ai_rate_limiter_admin_menu() {
    add_options_page(
        'AI Rate Limiter Settings',
        'AI Rate Limiter',
        'manage_options',
        'wp-ai-rate-limiter',
        'wp_ai_rate_limiter_settings_page'
    );
}

function wp_ai_rate_limiter_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('wp_ai_limiter_default_limit', intval($_POST['default_limit']));
        update_option('wp_ai_limiter_default_window', intval($_POST['default_window']));
        update_option('wp_ai_limiter_strategy', sanitize_text_field($_POST['strategy']));
        update_option('wp_ai_limiter_learning', isset($_POST['learning']));
        update_option('wp_ai_limiter_adaptive', isset($_POST['adaptive']));
        
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    
    $default_limit = get_option('wp_ai_limiter_default_limit', 100);
    $default_window = get_option('wp_ai_limiter_default_window', 3600);
    $strategy = get_option('wp_ai_limiter_strategy', 'exponential');
    $learning = get_option('wp_ai_limiter_learning', true);
    $adaptive = get_option('wp_ai_limiter_adaptive', true);
    
    ?>
    <div class="wrap">
        <h1>AI Rate Limiter Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Default Limit</th>
                    <td><input type="number" name="default_limit" value="<?php echo $default_limit; ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Default Window (seconds)</th>
                    <td><input type="number" name="default_window" value="<?php echo $default_window; ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Retry Strategy</th>
                    <td>
                        <select name="strategy">
                            <option value="exponential" <?php selected($strategy, 'exponential'); ?>>Exponential</option>
                            <option value="linear" <?php selected($strategy, 'linear'); ?>>Linear</option>
                            <option value="fixed" <?php selected($strategy, 'fixed'); ?>>Fixed</option>
                            <option value="jitter" <?php selected($strategy, 'jitter'); ?>>Jitter</option>
                            <option value="adaptive" <?php selected($strategy, 'adaptive'); ?>>Adaptive</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Learning Enabled</th>
                    <td><input type="checkbox" name="learning" <?php checked($learning); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">Adaptive Throttling</th>
                    <td><input type="checkbox" name="adaptive" <?php checked($adaptive); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_menu', 'wp_ai_rate_limiter_admin_menu'); 