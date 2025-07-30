<?php

namespace AhurSystem\AIRateLimiter\Strategies;

class ExponentialStrategy implements RetryStrategyInterface
{
    private int $baseDelay;
    
    public function __construct(int $baseDelay = 60)
    {
        $this->baseDelay = $baseDelay;
    }
    
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int
    {
        return (int)($this->baseDelay * pow(2, $attempts));
    }
    
    public function getName(): string
    {
        return 'exponential';
    }
    
    public function getDescription(): string
    {
        return 'Exponential backoff (60s, 120s, 240s, 480s...)';
    }
} 