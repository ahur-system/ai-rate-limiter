<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class AdaptiveStrategy implements RetryStrategyInterface
{
    private int $baseDelay;
    private \Redis $redis;
    private array $config;
    
    public function __construct(\Redis $redis, array $config, int $baseDelay = 60)
    {
        $this->redis = $redis;
        $this->config = $config;
        $this->baseDelay = $baseDelay;
    }
    
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int
    {
        $usageRatio = $usage['count'] / $limit;
        $patternDelay = $this->getPatternBasedDelay($context['key'] ?? '');
        
        return (int)($this->baseDelay * $usageRatio * $patternDelay);
    }
    
    private function getPatternBasedDelay(string $key): float
    {
        // Simple pattern-based delay calculation
        // In a real implementation, this would analyze historical patterns
        $patterns = $this->redis->zrange(
            "{$this->config['isolation_prefix']}patterns:{$key}",
            0,
            -1
        );
        
        if (empty($patterns)) {
            return 1.0; // Default multiplier
        }
        
        // Calculate average frequency
        $totalFrequency = 0;
        $count = 0;
        
        foreach ($patterns as $pattern) {
            $data = json_decode($pattern, true);
            if ($data && isset($data['frequency'])) {
                $totalFrequency += $data['frequency'];
                $count++;
            }
        }
        
        if ($count === 0) {
            return 1.0;
        }
        
        $avgFrequency = $totalFrequency / $count;
        
        // Higher frequency = higher delay
        return max(0.5, min(2.0, $avgFrequency / 10));
    }
    
    public function getName(): string
    {
        return 'adaptive';
    }
    
    public function getDescription(): string
    {
        return 'Adaptive based on usage patterns';
    }
} 