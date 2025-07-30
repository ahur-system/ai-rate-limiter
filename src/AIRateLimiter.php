<?php

declare(strict_types=1);

namespace AhurSystem\AIRateLimiter;

use Redis;
use JsonException;
use AhurSystem\AIRateLimiter\Strategies\RetryStrategyFactory;

/**
 * AI-Powered Rate Limiter with Adaptive Throttling
 * 
 * This innovative class provides intelligent rate limiting that adapts to usage patterns
 * using machine learning algorithms to optimize API performance and user experience.
 * 
 * Features:
 * - Adaptive rate limiting based on usage patterns
 * - AI-powered burst detection and handling
 * - Intelligent retry strategies
 * - Real-time performance optimization
 * - Multi-tenant support with isolation
 * 
 * @author Ahur System
 * @version 1.0.0
 */
class AIRateLimiter
{
    private Redis $redis;
    private array $config;
    private array $patterns = [];
    private float $learningRate = 0.1;
    private int $patternWindow = 3600; // 1 hour
    private int $maxBurstMultiplier = 3;
    private RetryStrategyFactory $strategyFactory;
    
    /**
     * Constructor
     * 
     * @param Redis $redis Redis instance for storage
     * @param array $config Configuration options
     */
    public function __construct(Redis $redis, array $config = [])
    {
        $this->redis = $redis;
        $this->config = array_merge([
            'default_limit' => 100,
            'default_window' => 3600,
            'burst_threshold' => 0.8,
            'learning_enabled' => true,
            'pattern_detection' => true,
            'adaptive_throttling' => true,
            'retry_strategy' => 'exponential',
            'max_retries' => 3,
            'isolation_prefix' => 'ai_limiter:',
            'base_delay' => 60
        ], $config);
        
        $this->strategyFactory = new RetryStrategyFactory($redis, $this->config);
    }
    
    /**
     * Check if request is allowed and update AI patterns
     * 
     * @param string $identifier User/API key identifier
     * @param string $endpoint API endpoint
     * @param array $context Additional context (user agent, IP, etc.)
     * @return RateLimitResult
     */
    public function check(string $identifier, string $endpoint = 'default', array $context = []): RateLimitResult
    {
        $key = $this->buildKey($identifier, $endpoint);
        $currentTime = time();
        
        // Get current usage
        $usage = $this->getCurrentUsage($key);
        
        // Analyze patterns and predict optimal limits
        $adaptiveLimit = $this->calculateAdaptiveLimit($identifier, $endpoint, $context);
        
        // Check if request is allowed
        $isAllowed = $usage['count'] < $adaptiveLimit;
        
        // Store current usage count for remaining calculation
        $currentUsageCount = $usage['count'];
        
        if ($isAllowed) {
            // Increment usage
            $this->incrementUsage($key, $currentTime);
            
            // Update AI patterns
            $this->updatePatterns($identifier, $endpoint, $context, $currentTime);
            
            // Reset retry attempts when request is allowed
            $this->resetRetryAttempts($key);
        } else {
            // Increment retry attempts when request is blocked
            $this->incrementRetryAttempts($key);
        }
        
        // Calculate intelligent retry delay only if request is blocked
        $retryDelay = $isAllowed ? 0 : $this->calculateRetryDelay($key, $usage, $adaptiveLimit);
        
        // Calculate remaining requests (use stored count to avoid off-by-one error)
        $remainingRequests = $isAllowed ? $adaptiveLimit - ($currentUsageCount + 1) : $adaptiveLimit - $currentUsageCount;
        
        return new RateLimitResult(
            $isAllowed,
            $remainingRequests,
            $retryDelay,
            $this->getResetTime($key),
            $this->getUsageStats($key, $adaptiveLimit)
        );
    }
    
    /**
     * Calculate adaptive rate limit based on AI analysis
     * 
     * @param string $identifier
     * @param string $endpoint
     * @param array $context
     * @return int
     */
    private function calculateAdaptiveLimit(string $identifier, string $endpoint, array $context): int
    {
        $baseLimit = $this->config['default_limit'];
        
        if (!$this->config['adaptive_throttling']) {
            return $baseLimit;
        }
        
        // Analyze historical patterns
        $patterns = $this->getPatterns($identifier, $endpoint);
        
        // Calculate usage trend
        $trend = $this->calculateUsageTrend($patterns);
        
        // Detect burst patterns
        $burstFactor = $this->detectBurstPattern($patterns);
        
        // Calculate optimal limit based on AI analysis
        $optimalLimit = $baseLimit;
        
        // Adjust based on usage patterns
        if ($trend > 0.7) {
            $optimalLimit = (int)($baseLimit * 1.2); // Increase for consistent users
        } elseif ($trend < 0.3) {
            $optimalLimit = (int)($baseLimit * 0.8); // Decrease for low usage
        }
        
        // Apply burst factor
        $optimalLimit = (int)($optimalLimit * $burstFactor);
        
        // Ensure minimum and maximum bounds
        return max(10, min($optimalLimit, $baseLimit * $this->maxBurstMultiplier));
    }
    
    /**
     * Calculate intelligent retry delay using strategy pattern
     * 
     * @param string $key
     * @param array $usage
     * @param int $limit
     * @return int
     */
    private function calculateRetryDelay(string $key, array $usage, int $limit): int
    {
        $attempts = $this->getRetryAttempts($key);
        $strategy = $this->strategyFactory->create($this->config['retry_strategy']);
        
        return $strategy->calculateDelay($attempts, $usage, $limit, ['key' => $key]);
    }
    
    /**
     * Update AI patterns with new request data
     * 
     * @param string $identifier
     * @param string $endpoint
     * @param int $timestamp
     * @param array $context
     */
    private function updatePatterns(string $identifier, string $endpoint, array $context, int $timestamp): void
    {
        if (!$this->config['learning_enabled']) {
            return;
        }
        
        $patternKey = "{$identifier}:{$endpoint}";
        
        $pattern = [
            'timestamp' => $timestamp,
            'hour' => (int)date('H', $timestamp),
            'day_of_week' => (int)date('N', $timestamp),
            'context' => $context,
            'frequency' => 1
        ];
        
        // Store pattern in Redis with TTL
        $this->redis->zadd(
            "{$this->config['isolation_prefix']}patterns:{$patternKey}",
            $timestamp,
            json_encode($pattern)
        );
        
        // Clean old patterns
        $this->redis->zremrangebyscore(
            "{$this->config['isolation_prefix']}patterns:{$patternKey}",
            '0',
            (string)($timestamp - $this->patternWindow)
        );
    }
    
    /**
     * Get current usage for a key
     * 
     * @param string $key
     * @return array
     */
    private function getCurrentUsage(string $key): array
    {
        $data = $this->redis->get("{$this->config['isolation_prefix']}usage:{$key}");
        
        if (!$data) {
            return ['count' => 0, 'reset_time' => time() + $this->config['default_window']];
        }
        
        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['count' => 0, 'reset_time' => time() + $this->config['default_window']];
        }
    }
    
    /**
     * Increment usage counter
     * 
     * @param string $key
     * @param int $timestamp
     */
    private function incrementUsage(string $key, int $timestamp): void
    {
        $usage = $this->getCurrentUsage($key);
        $usage['count']++;
        
        $this->redis->setex(
            "{$this->config['isolation_prefix']}usage:{$key}",
            $this->config['default_window'],
            json_encode($usage)
        );
    }
    
    /**
     * Build Redis key with isolation
     * 
     * @param string $identifier
     * @param string $endpoint
     * @return string
     */
    private function buildKey(string $identifier, string $endpoint): string
    {
        return "{$identifier}:{$endpoint}";
    }
    
    /**
     * Get patterns for analysis
     * 
     * @param string $identifier
     * @param string $endpoint
     * @return array
     */
    private function getPatterns(string $identifier, string $endpoint): array
    {
        $patternKey = "{$identifier}:{$endpoint}";
        $patterns = $this->redis->zrange(
            "{$this->config['isolation_prefix']}patterns:{$patternKey}",
            0,
            -1
        );
        
        return array_map(fn($p) => json_decode($p, true), $patterns);
    }
    
    /**
     * Calculate usage trend from patterns
     * 
     * @param array $patterns
     * @return float
     */
    private function calculateUsageTrend(array $patterns): float
    {
        if (empty($patterns)) {
            return 0.5; // Neutral trend
        }
        
        // Simple trend calculation based on frequency
        $totalFrequency = array_sum(array_column($patterns, 'frequency'));
        $avgFrequency = $totalFrequency / count($patterns);
        
        return min(1.0, max(0.0, $avgFrequency / 10)); // Normalize to 0-1
    }
    
    /**
     * Detect burst patterns
     * 
     * @param array $patterns
     * @return float
     */
    private function detectBurstPattern(array $patterns): float
    {
        if (empty($patterns)) {
            return 1.0;
        }
        
        // Analyze recent patterns for bursts
        $recentPatterns = array_filter($patterns, fn($p) => 
            $p['timestamp'] > time() - 300 // Last 5 minutes
        );
        
        if (empty($recentPatterns)) {
            return 1.0;
        }
        
        $burstCount = count($recentPatterns);
        $burstFactor = min($this->maxBurstMultiplier, 1 + ($burstCount * 0.1));
        
        return $burstFactor;
    }
    
    /**
     * Get retry attempts for a key
     * 
     * @param string $key
     * @return int
     */
    private function getRetryAttempts(string $key): int
    {
        $attempts = $this->redis->get("{$this->config['isolation_prefix']}retries:{$key}");
        return $attempts ? (int)$attempts : 0;
    }
    
    /**
     * Increment retry attempts for a key
     * 
     * @param string $key
     */
    private function incrementRetryAttempts(string $key): void
    {
        $attempts = $this->getRetryAttempts($key);
        $attempts++;
        
        $this->redis->setex(
            "{$this->config['isolation_prefix']}retries:{$key}",
            $this->config['default_window'],
            (string)$attempts
        );
    }
    
    /**
     * Reset retry attempts for a key
     * 
     * @param string $key
     */
    private function resetRetryAttempts(string $key): void
    {
        $this->redis->del("{$this->config['isolation_prefix']}retries:{$key}");
    }
    
    /**
     * Get pattern-based delay
     * 
     * @param string $key
     * @return float
     */
    private function getPatternBasedDelay(string $key): float
    {
        // Analyze patterns to determine optimal delay
        return 1.0; // Default multiplier
    }
    
    /**
     * Get reset time for a key
     * 
     * @param string $key
     * @return int
     */
    private function getResetTime(string $key): int
    {
        $usage = $this->getCurrentUsage($key);
        return $usage['reset_time'] ?? (time() + $this->config['default_window']);
    }
    
    /**
     * Get usage statistics
     * 
     * @param string $key
     * @param int $adaptiveLimit
     * @return array
     */
    private function getUsageStats(string $key, int $adaptiveLimit): array
    {
        $usage = $this->getCurrentUsage($key);
        $patterns = $this->getPatterns(explode(':', $key)[0], explode(':', $key)[1] ?? 'default');
        
        return [
            'current_usage' => $usage['count'],
            'pattern_count' => count($patterns),
            'trend' => $this->calculateUsageTrend($patterns),
            'burst_factor' => $this->detectBurstPattern($patterns),
            'base_limit' => $this->config['default_limit'],
            'adaptive_limit' => $adaptiveLimit
        ];
    }
    
    /**
     * Reset rate limit for a key
     * 
     * @param string $identifier
     * @param string $endpoint
     */
    public function reset(string $identifier, string $endpoint = 'default'): void
    {
        $key = $this->buildKey($identifier, $endpoint);
        $this->redis->del("{$this->config['isolation_prefix']}usage:{$key}");
        $this->redis->del("{$this->config['isolation_prefix']}retries:{$key}");
    }
    
    /**
     * Get configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Update configuration
     * 
     * @param array $config
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Get available retry strategies
     * 
     * @return array
     */
    public function getAvailableStrategies(): array
    {
        return $this->strategyFactory->getAvailableStrategies();
    }
    
    /**
     * Get strategy descriptions
     * 
     * @return array
     */
    public function getStrategyDescriptions(): array
    {
        return $this->strategyFactory->getStrategyDescriptions();
    }
} 