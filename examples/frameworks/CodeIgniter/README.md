# CodeIgniter Integration

This directory contains a complete CodeIgniter integration example for the AI Rate Limiter.

## Installation

```bash
composer require ahur-system/ai-rate-limiter
```

## Features

- **Filter**: Easy route protection
- **Service**: Dependency injection setup
- **Configuration**: Framework-specific config
- **Route Limits**: Per-route rate limiting
- **User Roles**: Role-based limits
- **HTTP Headers**: Standard rate limiting headers

## Usage

### Basic Filter Usage

```php
$routes->group('api', ['filter' => 'ai-rate-limit'], static function ($routes) {
    $routes->get('users', 'UserController::index');
});
```

### Configuration

```php
// app/Config/Filters.php
public array $filters = [
    'ai-rate-limit' => [
        'before' => ['api/*', 'admin/*']
    ]
];
```

## Files Included

- `app/Filters/AIRateLimitFilter.php` - Main filter
- `app/Config/Filters.php` - Filter configuration
- `app/Config/Routes.php` - Example routes
- `app/Controllers/API/UserController.php` - Example controller
- `composer.json` - Dependencies 