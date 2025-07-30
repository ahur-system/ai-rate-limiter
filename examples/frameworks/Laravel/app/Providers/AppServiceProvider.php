<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use App\Http\Middleware\AIRateLimitMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the AI Rate Limiter middleware
        $this->app['router']->aliasMiddleware('ai-rate-limit', AIRateLimitMiddleware::class);
        
        // Optionally, apply to all API routes globally
        if ($this->app->runningInConsole()) {
            return;
        }
        
        $kernel = $this->app->make(Kernel::class);
        
        // Add middleware to the global middleware stack if needed
        // $kernel->pushMiddleware(AIRateLimitMiddleware::class);
    }
} 