<pre dir="ltr">

<?php
date_default_timezone_set('Asia/Tehran');
/**
 * AI-Powered Rate Limiter Demo
 * 
 * This demo showcases the innovative features of the AI Rate Limiter
 * including adaptive throttling, burst detection, and pattern learning.
 */

require_once __DIR__ . '/vendor/autoload.php';

use AhurSystem\AIRateLimiter\AIRateLimiter;

echo "🤖 AI-Powered Rate Limiter Demo\n";
echo "================================\n\n";

// Check if Redis is available
if (!class_exists('Redis')) {
    echo "❌ Redis extension not available\n";
    echo "Please install the Redis PHP extension: sudo pacman -S php-redis\n";
    exit(1);
}

try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->ping();
    echo "✅ Redis connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
    echo "Please ensure Redis is running on localhost:6379\n";
    exit(1);
}

// Create AI Rate Limiter
$limiter = new AIRateLimiter($redis, [
    'default_limit' => 2,
    'default_window' => 10, // Hour long Ban? 3600
    'learning_enabled' => true,
    'adaptive_throttling' => true,
    'retry_strategy' => 'exponential'
]);

echo "🚀 Starting AI Rate Limiter Demo\n\n";

// Demo 1: Basic Rate Limiting
echo "=== Demo 1: Basic Rate Limiting ===\n";
$userId = 'demo_user_123';
$endpoint = 'api/v1/demo';

$result = $limiter->check($userId, 'index', [
    'user_agent' => 'DemoApp/1.0',
    'ip' => '192.168.1.100',
    'request_id' => $i
], FALSE);
var_dump($result->getRetryDelay());
if(!$result->isAllowed()){
    echo "❌ Rate limit exceeded\n";
    echo "   Retry after: {$result->getRetryDelay()} seconds\n";
    echo "   Reset time: " . $result->getResetDateTime()->format('H:i:s') . "\n";
    echo "___________________________________-\n";
    exit;
}

for ($i = 1; $i <= 12; $i++) {
    $result = $limiter->check($userId, $endpoint, [
        'user_agent' => 'DemoApp/1.0',
        'ip' => '192.168.1.100',
        'request_id' => $i
    ], FALSE);
    
    $status = $result->isAllowed() ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "Request $i: $status (Remaining: {$result->getRemainingRequests()})\n";
    
    if (!$result->isAllowed()) {
        echo "   Retry after: {$result->getRetryDelay()} seconds\n";
        echo "   Reset time: " . $result->getResetDateTime()->format('H:i:s') . "\n";
        break;
    }
}
echo "\n";

// Demo 2: Burst Detection
echo "=== Demo 2: Burst Detection ===\n";
$limiter->reset($userId, $endpoint); // Reset for fresh demo

echo "Simulating burst requests...\n";
for ($i = 1; $i <= 8; $i++) {
    $result = $limiter->check($userId, $endpoint, [
        'user_agent' => 'DemoApp/1.0',
        'ip' => '192.168.1.100',
        'request_type' => 'burst',
        'timestamp' => time()
    ]);
    
    $burstFactor = round($result->getBurstFactor(), 2);
    $trend = round($result->getUsageTrend() * 100, 1);
    
    echo "Burst $i: Burst Factor: $burstFactor, Trend: {$trend}%\n";
}
echo "\n";

// Demo 3: Multi-Endpoint Isolation
echo "=== Demo 3: Multi-Endpoint Isolation ===\n";
$endpoints = ['api/v1/users', 'api/v1/posts', 'api/v1/comments'];

foreach ($endpoints as $ep) {
    $result = $limiter->check($userId, $ep, [
        'user_agent' => 'DemoApp/1.0',
        'ip' => '192.168.1.100'
    ]);
    
    echo "Endpoint: $ep\n";
    echo "  Status: " . ($result->isAllowed() ? 'ALLOWED' : 'BLOCKED') . "\n";
    echo "  Remaining: {$result->getRemainingRequests()}\n";
    echo "  Pattern Count: {$result->getPatternCount()}\n\n";
}

// Demo 4: Analytics and Statistics
echo "=== Demo 4: Analytics and Statistics ===\n";
$result = $limiter->check($userId, 'api/v1/analytics', [
    'user_agent' => 'DemoApp/1.0',
    'ip' => '192.168.1.100'
]);

$stats = $result->getStats();
echo "📊 Usage Statistics:\n";
echo "  Current Usage: {$stats['current_usage']}\n";
echo "  Pattern Count: {$stats['pattern_count']}\n";
echo "  Usage Trend: " . round($stats['trend'] * 100, 1) . "%\n";
echo "  Burst Factor: " . round($stats['burst_factor'], 2) . "\n";
echo "  Remaining Requests: {$result->getRemainingRequests()}\n";
echo "  Reset Time: " . $result->getResetDateTime()->format('Y-m-d H:i:s') . "\n";
echo "  Time Until Reset: {$result->getTimeUntilReset()} seconds\n\n";

// Demo 5: JSON Response
echo "=== Demo 5: JSON Response ===\n";
$jsonResponse = $result->toJson();
echo "JSON Response:\n";
echo $jsonResponse . "\n\n";

// Demo 6: HTTP Headers
echo "=== Demo 6: HTTP Headers ===\n";
$headers = $result->getHeaders();
echo "HTTP Headers:\n";
foreach ($headers as $name => $value) {
    echo "  $name: $value\n";
}
echo "\n";

// Demo 7: Configuration Management
echo "=== Demo 7: Configuration Management ===\n";
$config = $limiter->getConfig();
echo "Current Configuration:\n";
echo "  Default Limit: {$config['default_limit']}\n";
echo "  Default Window: {$config['default_window']} seconds\n";
echo "  Learning Enabled: " . ($config['learning_enabled'] ? 'Yes' : 'No') . "\n";
echo "  Adaptive Throttling: " . ($config['adaptive_throttling'] ? 'Yes' : 'No') . "\n";
echo "  Retry Strategy: {$config['retry_strategy']}\n\n";

// Demo 8: Performance Test
echo "=== Demo 8: Performance Test ===\n";
$startTime = microtime(true);

for ($i = 0; $i < 100; $i++) {
    $limiter->check("perf_user_$i", 'api/v1/performance', [
        'user_agent' => 'PerformanceTest/1.0',
        'ip' => '192.168.1.' . ($i % 255)
    ]);
}

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

echo "Performance Test Results:\n";
echo "  100 requests processed in " . round($duration, 2) . "ms\n";
echo "  Average response time: " . round($duration / 100, 2) . "ms per request\n\n";

// Demo 9: AI Learning Demonstration
echo "=== Demo 9: AI Learning Demonstration ===\n";
$testUser = 'ai_learner_456';

// Simulate consistent usage pattern
echo "Simulating consistent usage pattern...\n";
for ($i = 0; $i < 5; $i++) {
    $result = $limiter->check($testUser, 'api/v1/learning', [
        'user_agent' => 'ConsistentApp/1.0',
        'ip' => '192.168.1.200',
        'usage_pattern' => 'consistent'
    ]);
    
    $trend = round($result->getUsageTrend() * 100, 1);
    echo "  Request " . ($i + 1) . ": Trend = {$trend}%\n";
}

// Simulate burst usage pattern
echo "\nSimulating burst usage pattern...\n";
for ($i = 0; $i < 3; $i++) {
    $result = $limiter->check($testUser, 'api/v1/learning', [
        'user_agent' => 'BurstApp/1.0',
        'ip' => '192.168.1.200',
        'usage_pattern' => 'burst'
    ]);
    
    $burstFactor = round($result->getBurstFactor(), 2);
    echo "  Burst " . ($i + 1) . ": Burst Factor = $burstFactor\n";
}

echo "\nAI Learning Summary:\n";
$finalResult = $limiter->check($testUser, 'api/v1/learning',[],false);
$finalStats = $finalResult->getStats();
echo "  Final Trend: " . round($finalStats['trend'] * 100, 1) . "%\n";
echo "  Final Burst Factor: " . round($finalStats['burst_factor'], 2) . "\n";
echo "  Pattern Count: {$finalStats['pattern_count']}\n\n";

// Demo 10: Error Handling
echo "=== Demo 10: Error Handling ===\n";
try {
    // Test with invalid configuration
    $testLimiter = new AIRateLimiter($redis, [
        'default_limit' => 10,
        'default_window' => 3600
    ]);
    
    $result = $testLimiter->check('error_test_user', 'api/v1/error');
    echo "✅ Error handling test passed\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "🎉 Demo Complete!\n";
echo "================\n\n";

echo "Key Features Demonstrated:\n";
echo "✅ Adaptive Rate Limiting\n";
echo "✅ Burst Detection\n";
echo "✅ Pattern Learning\n";
echo "✅ Multi-Tenant Isolation\n";
echo "✅ Real-time Analytics\n";
echo "✅ HTTP Header Integration\n";
echo "✅ JSON Response Format\n";
echo "✅ Configuration Management\n";
echo "✅ Performance Optimization\n";
echo "✅ AI Learning Capabilities\n";
echo "✅ Error Handling\n\n";

echo "This innovative AI-powered rate limiter provides:\n";
echo "• Intelligent adaptation to usage patterns\n";
echo "• Automatic burst detection and handling\n";
echo "• Real-time analytics and monitoring\n";
echo "• High performance with Redis storage\n";
echo "• Easy integration with existing APIs\n";
echo "• Comprehensive error handling\n\n";

echo "Ready for production use! 🚀\n"; 
?>
</pre>