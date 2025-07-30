# AI Rate Limiter Documentation

## 📖 Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Configuration](#configuration)
5. [Usage Examples](#usage-examples)
6. [API Reference](#api-reference)
7. [Framework Integrations](#framework-integrations)
8. [Troubleshooting](#troubleshooting)

## 🎯 Overview

The AI Rate Limiter is an intelligent PHP library that provides adaptive rate limiting using AI concepts like pattern learning and adaptive algorithms. It goes beyond traditional rate limiting by learning from user behavior and optimizing limits in real-time.

### Key Features

- **🤖 AI-Powered**: Pattern learning and adaptive algorithms
- **🎯 Strategy Pattern**: Multiple retry strategies
- **🔧 Framework Support**: Laravel, CodeIgniter, WordPress
- **📊 Analytics**: Real-time usage statistics
- **🚀 Performance**: Redis-based, high-performance

## 📦 Installation

### Requirements

- **PHP**: 8.1 or higher
- **Redis**: Server and PHP extension
- **Composer**: For dependency management

### Composer Installation

```bash
composer require ahur-system/ai-rate-limiter
```

### Manual Installation

```bash
git clone https://github.com/yourusername/ai-rate-limiter.git
cd ai-rate-limiter
composer install
```

## 🚀 Quick Start

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use AhurSystem\AIRateLimiter\AIRateLimiter;

// Initialize Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Create rate limiter
$limiter = new AIRateLimiter($redis, [
    'default_limit' => 100,
    'default_window' => 3600, // 1 hour
    'learning_enabled' => true,
    'adaptive_throttling' => true
]);

// Check rate limit
$result = $limiter->check('user_123', 'api/v1/users');

if ($result->isAllowed()) {
    echo "Request allowed! Remaining: " . $result->getRemainingRequests();
} else {
    echo "Rate limited! Retry after: " . $result->getRetryDelay() . " seconds";
}
```

### Namespace

**Main Namespace**: `AhurSystem\AIRateLimiter`

**Strategy Namespace**: `AhurSystem\AIRateLimiter\Strategies`

## ⚙️ Configuration

### Default Configuration

```php
$config = [
    'default_limit' => 100,           // Default requests per window
    'default_window' => 3600,         // Window in seconds (1 hour)
    'burst_threshold' => 0.8,         // Burst detection threshold
    'learning_enabled' => true,       // Enable AI learning
    'pattern_detection' => true,      // Enable pattern detection
    'adaptive_throttling' => true,    // Enable adaptive limits
    'retry_strategy' => 'exponential', // Retry strategy
    'max_retries' => 3,              // Maximum retry attempts
    'isolation_prefix' => 'ai_limiter:', // Redis key prefix
    'base_delay' => 60               // Base delay for retry strategies
];
```

### Available Retry Strategies

1. **exponential**: Exponential backoff (60s, 120s, 240s...)
2. **linear**: Linear increase (60s, 120s, 180s...)
3. **fixed**: Fixed delay (always 60s)
4. **jitter**: Exponential with random jitter
5. **adaptive**: Pattern-based adaptive delay

## 📚 Usage Examples

### Basic Rate Limiting

```php
$result = $limiter->check('user_123', 'api/v1/users');

echo "Allowed: " . ($result->isAllowed() ? 'Yes' : 'No') . "\n";
echo "Remaining: " . $result->getRemainingRequests() . "\n";
echo "Reset time: " . $result->getResetDateTime()->format('Y-m-d H:i:s') . "\n";
```

### API Integration

```php
$result = $limiter->check('api_key_456', 'api/v1/data');

// Set HTTP headers
foreach ($result->getHeaders() as $name => $value) {
    header("$name: $value");
}

// Check if rate limited
if (!$result->isAllowed()) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate limit exceeded',
        'retry_after' => $result->getRetryDelay()
    ]);
    exit;
}
```

### Usage Statistics

```php
$result = $limiter->check('user_123', 'api/v1/users');
$stats = $result->getStats();

echo "Current usage: " . $stats['current_usage'] . "\n";
echo "Pattern count: " . $stats['pattern_count'] . "\n";
echo "Usage trend: " . round($stats['trend'] * 100, 2) . "%\n";
echo "Burst factor: " . round($stats['burst_factor'], 2) . "\n";
```

### Different Retry Strategies

```php
// Exponential backoff (default)
$limiter = new AIRateLimiter($redis, [
    'retry_strategy' => 'exponential'
]);

// Linear increase
$limiter = new AIRateLimiter($redis, [
    'retry_strategy' => 'linear'
]);

// Fixed delay
$limiter = new AIRateLimiter($redis, [
    'retry_strategy' => 'fixed'
]);

// Jitter (exponential with randomness)
$limiter = new AIRateLimiter($redis, [
    'retry_strategy' => 'jitter'
]);

// Adaptive (pattern-based)
$limiter = new AIRateLimiter($redis, [
    'retry_strategy' => 'adaptive'
]);
```

### Reset Rate Limits

```php
// Reset limits for a specific user and endpoint
$limiter->reset('user_123', 'api/v1/users');
```

## 🔧 API Reference

### AIRateLimiter Class

#### Constructor

```php
public function __construct(Redis $redis, array $config = [])
```

**Parameters:**
- `$redis`: Redis instance for storage
- `$config`: Configuration array (optional)

#### Methods

##### check()

```php
public function check(string $identifier, string $endpoint = 'default', array $context = []): RateLimitResult
```

**Parameters:**
- `$identifier`: User/API key identifier
- `$endpoint`: API endpoint (default: 'default')
- `$context`: Additional context (optional)

**Returns:** `RateLimitResult` object

##### reset()

```php
public function reset(string $identifier, string $endpoint = 'default'): void
```

**Parameters:**
- `$identifier`: User/API key identifier
- `$endpoint`: API endpoint (default: 'default')

##### getConfig()

```php
public function getConfig(): array
```

**Returns:** Current configuration array

##### updateConfig()

```php
public function updateConfig(array $config): void
```

**Parameters:**
- `$config`: New configuration array

##### getAvailableStrategies()

```php
public function getAvailableStrategies(): array
```

**Returns:** Array of available retry strategy names

##### getStrategyDescriptions()

```php
public function getStrategyDescriptions(): array
```

**Returns:** Array of strategy names and descriptions

### RateLimitResult Class

#### Methods

##### isAllowed()

```php
public function isAllowed(): bool
```

**Returns:** Whether the request is allowed

##### getRemainingRequests()

```php
public function getRemainingRequests(): int
```

**Returns:** Number of remaining requests

##### getRetryDelay()

```php
public function getRetryDelay(): int
```

**Returns:** Seconds to wait before retrying

##### getResetDateTime()

```php
public function getResetDateTime(): DateTime
```

**Returns:** DateTime when the limit resets

##### getHeaders()

```php
public function getHeaders(): array
```

**Returns:** Array of HTTP headers for rate limiting

##### getStats()

```php
public function getStats(): array
```

**Returns:** Usage statistics array

##### toArray()

```php
public function toArray(): array
```

**Returns:** Array representation of the result

##### toJson()

```php
public function toJson(): string
```

**Returns:** JSON representation of the result

## 🔧 Framework Integrations

### Laravel

See `examples/frameworks/Laravel/` for complete integration.

### CodeIgniter

See `examples/frameworks/CodeIgniter/` for complete integration.

### WordPress

See `examples/frameworks/WordPress/` for complete integration.

## 🔍 AI Features Explained

### Adaptive Rate Limiting

The AI analyzes usage patterns to determine optimal rate limits:

- **Consistent Users**: Get higher limits for predictable usage
- **Low Usage**: Reduced limits to conserve resources
- **Burst Detection**: Intelligent handling of traffic spikes
- **Pattern Recognition**: Learns from historical data

### Pattern Analysis

The system tracks and analyzes:
- Request frequency over time
- Time-of-day patterns
- Day-of-week patterns
- User agent and IP patterns
- Endpoint-specific behavior

### Intelligent Retry Strategies

- **Exponential Backoff**: Standard exponential retry delays
- **Pattern-Based**: Delays based on usage patterns
- **Adaptive**: Adjusts delays based on current load

## 📊 Analytics and Monitoring

### Usage Statistics

The library provides detailed usage statistics:

```php
$stats = $result->getStats();

// Available statistics:
// - current_usage: Current request count
// - pattern_count: Number of stored patterns
// - trend: Usage trend (0.0 to 1.0)
// - burst_factor: Burst detection factor
// - adaptive_limit: Current adaptive limit
// - base_limit: Original base limit
```

### HTTP Headers

Standard rate limiting headers are provided:

```php
$headers = $result->getHeaders();

// Available headers:
// - X-RateLimit-Limit: Maximum requests allowed
// - X-RateLimit-Remaining: Remaining requests
// - X-RateLimit-Reset: Reset time
// - Retry-After: Seconds to wait before retrying
```

## 🚨 Troubleshooting

### Common Issues

#### Redis Connection Failed

**Error:** `Redis connection failed`

**Solution:**
1. Ensure Redis server is running
2. Check Redis host and port configuration
3. Verify Redis extension is installed

```bash
# Check Redis server
redis-cli ping

# Install Redis extension
sudo apt-get install php-redis  # Ubuntu/Debian
sudo yum install php-redis      # CentOS/RHEL
pecl install redis              # macOS
```

#### Rate Limits Too Strict

**Issue:** Users are being rate limited too aggressively

**Solution:**
1. Increase default limits
2. Enable adaptive throttling
3. Adjust burst threshold

```php
$limiter = new AIRateLimiter($redis, [
    'default_limit' => 200,        // Increase limit
    'burst_threshold' => 0.9,      // Higher threshold
    'adaptive_throttling' => true  // Enable adaptive
]);
```

#### Performance Issues

**Issue:** Slow response times

**Solution:**
1. Check Redis performance
2. Monitor memory usage
3. Optimize pattern cleanup

```php
// Reduce pattern window for better performance
$limiter = new AIRateLimiter($redis, [
    'pattern_window' => 1800,  // 30 minutes instead of 1 hour
]);
```

### Debug Mode

Enable debug mode for detailed error messages:

```php
// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check Redis connection
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    echo "Redis connection successful\n";
} catch (Exception $e) {
    echo "Redis connection failed: " . $e->getMessage() . "\n";
}
```

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/ai-rate-limiter/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/ai-rate-limiter/wiki)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/ai-rate-limiter/discussions)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Author**: Ali Khaleghi    
**GitHub**: https://github.com/ahur-system/ai-rate-limiter 