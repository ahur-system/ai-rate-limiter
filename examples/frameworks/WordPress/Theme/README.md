# WordPress Theme AI Rate Limiter Integration

This example shows how to integrate the AI Rate Limiter into a WordPress theme.

## Installation

1. **Install Composer dependencies:**
```bash
composer install
```

2. **Add Redis configuration to `wp-config.php`:**
```php
// Redis Configuration for AI Rate Limiter
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
```

3. **Copy `functions.php` to your theme directory**

## Features

### Protected Endpoints

- **AJAX Requests**: All AJAX endpoints are automatically protected
- **REST API**: WordPress REST API endpoints are rate limited
- **Comment Forms**: Comment submissions are protected from spam
- **Custom Endpoints**: Easy to add protection to custom endpoints

### User Identification

- **Logged-in Users**: Uses WordPress user ID
- **API Keys**: Supports API key authentication via headers
- **IP Fallback**: Falls back to IP address for anonymous users
- **Role-based Limits**: Different limits for different user roles

### Configuration

The theme includes an admin settings page at **Settings > AI Rate Limiter** with options for:

- Default request limit
- Time window
- Retry strategy (exponential, linear, fixed, jitter, adaptive)
- Learning enabled/disabled
- Adaptive throttling enabled/disabled

## Usage Examples

### Protect Custom AJAX Endpoint

```php
// In your theme's functions.php
add_action('wp_ajax_my_custom_action', 'my_custom_ajax_handler');
add_action('wp_ajax_nopriv_my_custom_action', 'my_custom_ajax_handler');

function my_custom_ajax_handler() {
    // Rate limiting is automatically applied
    // Your custom logic here
    wp_send_json_success(['message' => 'Success']);
}
```

### Protect Custom REST API Endpoint

```php
add_action('rest_api_init', function () {
    register_rest_route('my-plugin/v1', '/data', [
        'methods' => 'GET',
        'callback' => 'my_rest_callback',
        'permission_callback' => '__return_true'
    ]);
});

function my_rest_callback($request) {
    // Rate limiting is automatically applied
    return new WP_REST_Response(['data' => 'success'], 200);
}
```

### Manual Rate Limit Check

```php
// Check rate limit manually
$result = wp_ai_rate_limiter_check('custom_endpoint');

if ($result && !$result->isAllowed()) {
    wp_die('Rate limit exceeded. Please try again later.');
}
```

## Response Headers

The integration adds these headers to responses:

- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Reset time
- `Retry-After`: Seconds to wait before retrying

## Error Responses

When rate limited, the integration returns:

### AJAX Responses
```json
{
    "error": "Rate limit exceeded",
    "retry_after": 120,
    "reset_time": "2024-01-15 10:30:00"
}
```

### REST API Responses
```json
{
    "code": "rate_limit_exceeded",
    "message": "Rate limit exceeded",
    "data": {
        "status": 429,
        "retry_after": 120,
        "reset_time": "2024-01-15 10:30:00"
    }
}
```

## Configuration Options

### WordPress Options

- `wp_ai_limiter_default_limit`: Default request limit (default: 100)
- `wp_ai_limiter_default_window`: Time window in seconds (default: 3600)
- `wp_ai_limiter_strategy`: Retry strategy (default: exponential)
- `wp_ai_limiter_learning`: Enable AI learning (default: true)
- `wp_ai_limiter_adaptive`: Enable adaptive throttling (default: true)

### Available Strategies

- **exponential**: Exponential backoff (60s, 120s, 240s...)
- **linear**: Linear increase (60s, 120s, 180s...)
- **fixed**: Fixed delay (always 60s)
- **jitter**: Exponential with random jitter
- **adaptive**: Based on usage patterns

## Security Features

- **Admin Bypass**: Administrators are not rate limited
- **Role-based Protection**: Different limits for different user roles
- **IP Validation**: Proper IP address detection
- **Sanitization**: All inputs are properly sanitized
- **Error Logging**: Failed initializations are logged

## Performance

- **Redis-based**: High-performance Redis storage
- **Lazy Loading**: Only initializes when needed
- **Caching**: Efficient caching of rate limit data
- **Cleanup**: Automatic cleanup of old data

## Troubleshooting

### Redis Connection Issues

If Redis is not available, the rate limiter will gracefully fail and log an error. Your site will continue to function normally.

### Performance Issues

- Ensure Redis is properly configured
- Check Redis memory usage
- Monitor rate limit patterns in admin panel

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
``` 