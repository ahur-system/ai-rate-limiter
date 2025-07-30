<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class LinearStrategy implements RetryStrategyInterface
{
    private int $baseDelay;
    
    public function __construct(int $baseDelay = 60)
    {
        $this->baseDelay = $baseDelay;
    }
    
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int
    {
        return (int)($this->baseDelay * (1 + $attempts));
    }
    
    public function getName(): string
    {
        return 'linear';
    }
    
    public function getDescription(): string
    {
        return 'Linear increase (60s, 120s, 180s, 240s...)';
    }
} 