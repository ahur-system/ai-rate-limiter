<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use AhurSystem\AIRateLimiter\AIRateLimiter;
use Redis;

class AIRateLimitFilter implements FilterInterface
{
    private AIRateLimiter $limiter;
    
    public function __construct()
    {
        $redis = new Redis();
        $redis->connect(
            env('REDIS_HOST', '127.0.0.1'),
            env('REDIS_PORT', 6379)
        );
        
        $this->limiter = new AIRateLimiter($redis, [
            'default_limit' => env('AI_RATE_LIMITER_DEFAULT_LIMIT', 100),
            'default_window' => env('AI_RATE_LIMITER_DEFAULT_WINDOW', 3600),
            'retry_strategy' => env('AI_RATE_LIMITER_RETRY_STRATEGY', 'exponential'),
            'learning_enabled' => env('AI_RATE_LIMITER_LEARNING_ENABLED', true),
            'adaptive_throttling' => env('AI_RATE_LIMITER_ADAPTIVE_THROTTLING', true),
            'isolation_prefix' => env('AI_RATE_LIMITER_ISOLATION_PREFIX', 'ci_ai_limiter:')
        ]);
    }
    
    /**
     * Do something before the request is processed
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get identifier (user ID, API key, or IP)
        $identifier = $this->getIdentifier($request);
        
        // Get endpoint
        $endpoint = $request->getUri()->getPath();
        
        // Context for AI analysis
        $context = [
            'user_agent' => $request->getUserAgent(),
            'ip' => $request->getIPAddress(),
            'method' => $request->getMethod(),
            'user_id' => session()->get('user_id')
        ];
        
        // Check rate limit
        $result = $this->limiter->check($identifier, $endpoint, $context);
        
        // If rate limited, return error response
        if (!$result->isAllowed()) {
            $response = service('response');
            $response->setStatusCode(429);
            $response->setJSON([
                'error' => 'Rate limit exceeded',
                'retry_after' => $result->getRetryDelay(),
                'reset_time' => $result->getResetDateTime()->format('Y-m-d H:i:s')
            ]);
            
            // Add rate limit headers
            foreach ($result->getHeaders() as $name => $value) {
                $response->setHeader($name, $value);
            }
            
            return $response;
        }
        
        return $request;
    }
    
    /**
     * Do something after the request is processed
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add rate limit headers to successful responses
        $identifier = $this->getIdentifier($request);
        $endpoint = $request->getUri()->getPath();
        
        $context = [
            'user_agent' => $request->getUserAgent(),
            'ip' => $request->getIPAddress(),
            'method' => $request->getMethod(),
            'user_id' => session()->get('user_id')
        ];
        
        $result = $this->limiter->check($identifier, $endpoint, $context);
        
        foreach ($result->getHeaders() as $name => $value) {
            $response->setHeader($name, $value);
        }
        
        return $response;
    }
    
    /**
     * Get identifier for rate limiting
     */
    private function getIdentifier(RequestInterface $request): string
    {
        // Try to get user ID from session
        if ($userId = session()->get('user_id')) {
            return "user_{$userId}";
        }
        
        // Try to get API key from header
        if ($apiKey = $request->getHeaderLine('X-API-Key')) {
            return "api_{$apiKey}";
        }
        
        // Fallback to IP address
        return "ip_{$request->getIPAddress()}";
    }
} 