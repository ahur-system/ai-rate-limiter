<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AhurSystem\AIRateLimiter\AIRateLimiter;

// Initialize Redis connection
if (!class_exists('Redis')) {
    echo "❌ Redis extension not available\n";
    echo "Please install the Redis PHP extension: sudo pacman -S php-redis\n";
    exit(1);
}

try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
} catch (Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
    echo "Please ensure Redis is running on localhost:6379\n";
    exit(1);
}

// Create AI Rate Limiter instance
$limiter = new AIRateLimiter($redis, [
    'default_limit' => 100,
    'default_window' => 3600, // 1 hour
    'learning_enabled' => true,
    'adaptive_throttling' => true,
    'retry_strategy' => 'adaptive'
]);

// Example 1: Basic rate limiting
echo "=== Basic Rate Limiting Example ===\n";

$userId = 'user_123';
$endpoint = 'api/v1/users';

$result = $limiter->check($userId, $endpoint, [
    'user_agent' => 'MyApp/1.0',
    'ip' => '192.168.1.100'
]);

echo "Request allowed: " . ($result->isAllowed() ? 'Yes' : 'No') . "\n";
echo "Remaining requests: " . $result->getRemainingRequests() . "\n";
echo "Retry delay: " . $result->getRetryDelay() . " seconds\n";
echo "Reset time: " . $result->getResetDateTime()->format('Y-m-d H:i:s') . "\n";
echo "Usage trend: " . round($result->getUsageTrend() * 100, 2) . "%\n";
echo "Burst factor: " . round($result->getBurstFactor(), 2) . "\n\n";

// Example 2: Simulate burst requests
echo "=== Burst Detection Example ===\n";

for ($i = 0; $i < 5; $i++) {
    $result = $limiter->check($userId, $endpoint, [
        'user_agent' => 'MyApp/1.0',
        'ip' => '192.168.1.100',
        'request_type' => 'burst'
    ]);
    
    echo "Request " . ($i + 1) . ": " . ($result->isAllowed() ? 'Allowed' : 'Blocked') . 
         " (Burst factor: " . round($result->getBurstFactor(), 2) . ")\n";
}

// Example 3: Different endpoints
echo "\n=== Multi-Endpoint Example ===\n";

$endpoints = ['api/v1/users', 'api/v1/posts', 'api/v1/comments'];

foreach ($endpoints as $endpoint) {
    $result = $limiter->check($userId, $endpoint, [
        'user_agent' => 'MyApp/1.0',
        'ip' => '192.168.1.100'
    ]);
    
    echo "Endpoint: $endpoint\n";
    echo "  Allowed: " . ($result->isAllowed() ? 'Yes' : 'No') . "\n";
    echo "  Remaining: " . $result->getRemainingRequests() . "\n";
    echo "  Pattern count: " . $result->getPatternCount() . "\n\n";
}

// Example 4: API response headers
echo "=== HTTP Headers Example ===\n";

$result = $limiter->check($userId, 'api/v1/data');
$headers = $result->getHeaders();

echo "HTTP Headers:\n";
foreach ($headers as $name => $value) {
    echo "  $name: $value\n";
}

// Example 5: JSON response
echo "\n=== JSON Response Example ===\n";

$jsonResponse = $result->toJson();
echo $jsonResponse . "\n";

// Example 6: Reset rate limits
echo "\n=== Reset Example ===\n";

echo "Before reset - Remaining: " . $result->getRemainingRequests() . "\n";
$limiter->reset($userId, 'api/v1/users');
$result = $limiter->check($userId, 'api/v1/users');
echo "After reset - Remaining: " . $result->getRemainingRequests() . "\n"; 