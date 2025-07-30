<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class RetryStrategyFactory
{
    private \Redis $redis;
    private array $config;
    
    public function __construct(\Redis $redis, array $config)
    {
        $this->redis = $redis;
        $this->config = $config;
    }
    
    /**
     * Create a retry strategy by name
     * 
     * @param string $strategyName
     * @return RetryStrategyInterface
     * @throws \InvalidArgumentException
     */
    public function create(string $strategyName): RetryStrategyInterface
    {
        $baseDelay = $this->config['base_delay'] ?? 60;
        
        return match ($strategyName) {
            'exponential' => new ExponentialStrategy($baseDelay),
            'linear' => new LinearStrategy($baseDelay),
            'fixed' => new FixedStrategy($baseDelay),
            'jitter' => new JitterStrategy($baseDelay),
            'adaptive' => new AdaptiveStrategy($this->redis, $this->config, $baseDelay),
            default => throw new \InvalidArgumentException("Unknown retry strategy: $strategyName")
        };
    }
    
    /**
     * Get all available strategy names
     * 
     * @return array
     */
    public function getAvailableStrategies(): array
    {
        return [
            'exponential',
            'linear', 
            'fixed',
            'jitter',
            'adaptive'
        ];
    }
    
    /**
     * Get strategy descriptions
     * 
     * @return array
     */
    public function getStrategyDescriptions(): array
    {
        $descriptions = [];
        foreach ($this->getAvailableStrategies() as $strategy) {
            $instance = $this->create($strategy);
            $descriptions[$strategy] = $instance->getDescription();
        }
        return $descriptions;
    }
} 