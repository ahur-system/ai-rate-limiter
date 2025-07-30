# Laravel Integration

This directory contains a complete Laravel integration example for the AI Rate Limiter.

## Installation

```bash
composer require ahur-system/ai-rate-limiter
```

## Features

- **Middleware**: Easy route protection
- **Service Provider**: Dependency injection setup
- **Configuration**: Framework-specific config
- **Route Limits**: Per-route rate limiting
- **User Roles**: Role-based limits
- **HTTP Headers**: Standard rate limiting headers

## Usage

### Basic Middleware Usage

```php
Route::middleware(['ai-rate-limit'])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
});
```

### Configuration

```php
// config/ai-rate-limiter.php
return [
    'default_limit' => env('AI_RATE_LIMITER_DEFAULT_LIMIT', 100),
    'route_limits' => [
        'api/v1/users' => ['limit' => 50, 'strategy' => 'exponential'],
        'api/v1/posts' => ['limit' => 200, 'strategy' => 'linear'],
    ],
    'role_limits' => [
        'admin' => ['limit' => 1000, 'strategy' => 'adaptive'],
        'premium' => ['limit' => 500, 'strategy' => 'exponential'],
    ]
];
```

## Files Included

- `app/Http/Middleware/AIRateLimitMiddleware.php` - Main middleware
- `app/Providers/AppServiceProvider.php` - Service registration
- `config/ai-rate-limiter.php` - Configuration file
- `routes/api.php` - Example routes
- `composer.json` - Dependencies 