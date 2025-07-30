<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AhurSystem\AIRateLimiter\AIRateLimiter;
use Redis;

class AIRateLimitMiddleware
{
    private AIRateLimiter $limiter;
    
    public function __construct()
    {
        $redis = new Redis();
        $redis->connect(
            config('database.redis.default.host', '127.0.0.1'),
            config('database.redis.default.port', 6379)
        );
        
        $this->limiter = new AIRateLimiter($redis, [
            'default_limit' => config('ai-rate-limiter.default_limit', 100),
            'default_window' => config('ai-rate-limiter.default_window', 3600),
            'retry_strategy' => config('ai-rate-limiter.retry_strategy', 'exponential'),
            'learning_enabled' => config('ai-rate-limiter.learning_enabled', true),
            'adaptive_throttling' => config('ai-rate-limiter.adaptive_throttling', true),
            'isolation_prefix' => config('ai-rate-limiter.isolation_prefix', 'laravel_ai_limiter:')
        ]);
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get identifier (user ID, API key, or IP)
        $identifier = $this->getIdentifier($request);
        
        // Get endpoint
        $endpoint = $request->route() ? $request->route()->getName() : $request->path();
        
        // Context for AI analysis
        $context = [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id
        ];
        
        // Check rate limit
        $result = $this->limiter->check($identifier, $endpoint, $context);
        
        // Add headers to response
        $response = $next($request);
        
        foreach ($result->getHeaders() as $name => $value) {
            $response->header($name, $value);
        }
        
        // If rate limited, return error response
        if (!$result->isAllowed()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $result->getRetryDelay(),
                'reset_time' => $result->getResetDateTime()->format('Y-m-d H:i:s')
            ], 429);
        }
        
        return $response;
    }
    
    /**
     * Get identifier for rate limiting
     */
    private function getIdentifier(Request $request): string
    {
        // Try to get user ID first
        if ($user = $request->user()) {
            return "user_{$user->id}";
        }
        
        // Try to get API key from header
        if ($apiKey = $request->header('X-API-Key')) {
            return "api_{$apiKey}";
        }
        
        // Fallback to IP address
        return "ip_{$request->ip()}";
    }
} 