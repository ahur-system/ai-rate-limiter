<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class JitterStrategy implements RetryStrategyInterface
{
    private int $baseDelay;
    private float $jitterFactor;
    
    public function __construct(int $baseDelay = 60, float $jitterFactor = 0.1)
    {
        $this->baseDelay = $baseDelay;
        $this->jitterFactor = $jitterFactor;
    }
    
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int
    {
        $exponentialDelay = (int)($this->baseDelay * pow(2, $attempts));
        $jitter = rand(0, (int)($exponentialDelay * $this->jitterFactor));
        
        return $exponentialDelay + $jitter;
    }
    
    public function getName(): string
    {
        return 'jitter';
    }
    
    public function getDescription(): string
    {
        return 'Exponential with jitter (60s±6s, 120s±12s, 240s±24s...)';
    }
} 