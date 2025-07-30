<?php

declare(strict_types=1);

namespace AhurSystem\AIRateLimiter;

/**
 * Rate Limit Result
 * 
 * Encapsulates the result of a rate limit check with detailed information
 * about the current state and recommendations.
 */
class RateLimitResult
{
    private bool $isAllowed;
    private int $remainingRequests;
    private int $retryDelay;
    private int $resetTime;
    private array $stats;
    
    /**
     * Constructor
     * 
     * @param bool $isAllowed Whether the request is allowed
     * @param int $remainingRequests Number of remaining requests
     * @param int $retryDelay Recommended retry delay in seconds
     * @param int $resetTime Unix timestamp when limits reset
     * @param array $stats Additional statistics
     */
    public function __construct(
        bool $isAllowed,
        int $remainingRequests,
        int $retryDelay,
        int $resetTime,
        array $stats = []
    ) {
        $this->isAllowed = $isAllowed;
        $this->remainingRequests = $remainingRequests;
        $this->retryDelay = $retryDelay;
        $this->resetTime = $resetTime;
        $this->stats = $stats;
    }
    
    /**
     * Check if request is allowed
     * 
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->isAllowed;
    }
    
    /**
     * Get remaining requests
     * 
     * @return int
     */
    public function getRemainingRequests(): int
    {
        return $this->remainingRequests;
    }
    
    /**
     * Get retry delay in seconds
     * 
     * @return int
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
    
    /**
     * Get reset time as Unix timestamp
     * 
     * @return int
     */
    public function getResetTime(): int
    {
        return $this->resetTime;
    }
    
    /**
     * Get reset time as DateTime
     * 
     * @return \DateTime
     */
    public function getResetDateTime(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->resetTime);
    }
    
    /**
     * Get time until reset in seconds
     * 
     * @return int
     */
    public function getTimeUntilReset(): int
    {
        return max(0, $this->resetTime - time());
    }
    
    /**
     * Get usage statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
    
    /**
     * Get current usage count
     * 
     * @return int
     */
    public function getCurrentUsage(): int
    {
        return $this->stats['current_usage'] ?? 0;
    }
    
    /**
     * Get pattern count
     * 
     * @return int
     */
    public function getPatternCount(): int
    {
        return $this->stats['pattern_count'] ?? 0;
    }
    
    /**
     * Get usage trend (0-1)
     * 
     * @return float
     */
    public function getUsageTrend(): float
    {
        return $this->stats['trend'] ?? 0.5;
    }
    
    /**
     * Get burst factor
     * 
     * @return float
     */
    public function getBurstFactor(): float
    {
        return $this->stats['burst_factor'] ?? 1.0;
    }
    
    /**
     * Convert to array for API responses
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->isAllowed,
            'remaining' => $this->remainingRequests,
            'retry_after' => $this->retryDelay,
            'reset_time' => $this->resetTime,
            'reset_time_iso' => $this->getResetDateTime()->format('c'),
            'time_until_reset' => $this->getTimeUntilReset(),
            'stats' => $this->stats,
        ];
    }
    
    /**
     * Convert to JSON for API responses
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
    
    /**
     * Get HTTP headers for rate limiting
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [
            'X-RateLimit-Limit' => $this->stats['base_limit'] ?? 100,
            'X-RateLimit-Remaining' => $this->remainingRequests,
            'X-RateLimit-Reset' => $this->resetTime,
            'X-RateLimit-Reset-ISO' => $this->getResetDateTime()->format('c'),
        ];
        
        if (!$this->isAllowed) {
            $headers['Retry-After'] = $this->retryDelay;
            $headers['X-RateLimit-Retry-After'] = $this->retryDelay;
        }
        
        return $headers;
    }
} 