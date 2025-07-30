<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Rate Limiter Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the AI Rate Limiter
    | middleware and service.
    |
    */

    'default_limit' => env('AI_RATE_LIMITER_DEFAULT_LIMIT', 100),
    
    'default_window' => env('AI_RATE_LIMITER_DEFAULT_WINDOW', 3600),
    
    'retry_strategy' => env('AI_RATE_LIMITER_RETRY_STRATEGY', 'exponential'),
    
    'learning_enabled' => env('AI_RATE_LIMITER_LEARNING_ENABLED', true),
    
    'adaptive_throttling' => env('AI_RATE_LIMITER_ADAPTIVE_THROTTLING', true),
    
    'burst_threshold' => env('AI_RATE_LIMITER_BURST_THRESHOLD', 0.8),
    
    'max_retries' => env('AI_RATE_LIMITER_MAX_RETRIES', 3),
    
    'isolation_prefix' => env('AI_RATE_LIMITER_ISOLATION_PREFIX', 'laravel_ai_limiter:'),
    
    'base_delay' => env('AI_RATE_LIMITER_BASE_DELAY', 60),
    
    /*
    |--------------------------------------------------------------------------
    | Route-specific limits
    |--------------------------------------------------------------------------
    |
    | Define different limits for specific routes or route groups.
    |
    */
    'route_limits' => [
        'api/v1/users' => [
            'limit' => 50,
            'window' => 3600,
            'strategy' => 'exponential'
        ],
        'api/v1/posts' => [
            'limit' => 200,
            'window' => 3600,
            'strategy' => 'linear'
        ],
        'api/v1/admin/*' => [
            'limit' => 1000,
            'window' => 3600,
            'strategy' => 'adaptive'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | User role limits
    |--------------------------------------------------------------------------
    |
    | Define different limits based on user roles.
    |
    */
    'role_limits' => [
        'admin' => [
            'limit' => 1000,
            'window' => 3600,
            'strategy' => 'adaptive'
        ],
        'premium' => [
            'limit' => 500,
            'window' => 3600,
            'strategy' => 'exponential'
        ],
        'free' => [
            'limit' => 100,
            'window' => 3600,
            'strategy' => 'fixed'
        ]
    ]
]; 