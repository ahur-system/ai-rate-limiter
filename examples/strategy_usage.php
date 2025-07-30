<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AhurSystem\AIRateLimiter\AIRateLimiter;

// Initialize Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo "🤖 AI Rate Limiter - Strategy Pattern Demo\n";
echo "==========================================\n\n";

// Test different retry strategies
$strategies = ['exponential', 'linear', 'fixed', 'jitter', 'adaptive'];

foreach ($strategies as $strategy) {
    echo "=== Testing $strategy Strategy ===\n";
    
    $limiter = new AIRateLimiter($redis, [
        'default_limit' => 10,
        'default_window' => 60,
        'retry_strategy' => $strategy,
        'learning_enabled' => true
    ]);
    
    // Simulate rate limiting
    for ($i = 1; $i <= 12; $i++) {
        $result = $limiter->check('test_user', 'api/v1/test');
        
        if (!$result->isAllowed()) {
            echo "❌ Request $i: BLOCKED\n";
            echo "   Retry delay: " . $result->getRetryDelay() . " seconds\n";
            echo "   Strategy: $strategy\n";
            echo "   Remaining: " . $result->getRemainingRequests() . "\n\n";
            break;
        } else {
            echo "✅ Request $i: ALLOWED (Remaining: " . $result->getRemainingRequests() . ")\n";
        }
    }
    
    echo "\n";
}

// Show strategy descriptions
echo "=== Available Strategies ===\n";
$limiter = new AIRateLimiter($redis);
$descriptions = $limiter->getStrategyDescriptions();

foreach ($descriptions as $name => $description) {
    echo "• $name: $description\n";
}

echo "\n🎯 Strategy Pattern Implementation Complete!\n"; 