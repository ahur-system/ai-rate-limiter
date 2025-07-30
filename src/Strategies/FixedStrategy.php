<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class FixedStrategy implements RetryStrategyInterface
{
    private int $baseDelay;
    
    public function __construct(int $baseDelay = 60)
    {
        $this->baseDelay = $baseDelay;
    }
    
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int
    {
        return $this->baseDelay;
    }
    
    public function getName(): string
    {
        return 'fixed';
    }
    
    public function getDescription(): string
    {
        return 'Fixed delay (always 60s)';
    }
} 