<?php

namespace AhurSystem\AIRateLimiter\Strategies;

interface RetryStrategyInterface
{
    /**
     * Calculate retry delay based on attempts and context
     * 
     * @param int $attempts Number of retry attempts
     * @param array $usage Current usage data
     * @param int $limit Current limit
     * @param array $context Additional context data
     * @return int Delay in seconds
     */
    public function calculateDelay(int $attempts, array $usage, int $limit, array $context = []): int;
    
    /**
     * Get strategy name
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Get strategy description
     * 
     * @return string
     */
    public function getDescription(): string;
} 