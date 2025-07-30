# AI Rate Limiter Demos

This directory contains demonstration scripts for the AI Rate Limiter library.

## Available Demos

### demo.php
A comprehensive demonstration showing:
- Basic rate limiting functionality
- Burst detection
- Multi-endpoint isolation
- Analytics and statistics
- JSON and HTTP header responses
- Configuration options
- Performance testing
- AI learning features

## Running the Demos

### Prerequisites
1. **Redis Server**: Make sure Redis is running on `127.0.0.1:6379`
2. **PHP Redis Extension**: Install the Redis extension for PHP
3. **Composer Dependencies**: Install the library dependencies

### Installation
```bash
# Install dependencies
composer install

# Run the main demo
php examples/demos/demo.php
```

### Running Individual Demos
```bash
# Basic usage example
php examples/basic_usage.php

# Strategy usage example
php examples/strategy_usage.php
```

## Demo Features

### 🤖 AI-Powered Features
- **Pattern Learning**: Demonstrates how the system learns from usage patterns
- **Adaptive Throttling**: Shows dynamic limit adjustment based on behavior
- **Burst Detection**: Illustrates intelligent burst pattern recognition
- **Context Awareness**: Shows how different contexts affect rate limiting

### 📊 Analytics
- **Usage Statistics**: Real-time usage tracking and reporting
- **Performance Metrics**: Response time and throughput analysis
- **Pattern Analysis**: Historical data analysis and trends

### 🔧 Technical Features
- **Multiple Strategies**: Exponential, linear, fixed, jitter, and adaptive
- **HTTP Headers**: Standard rate limiting headers
- **JSON Responses**: Structured API responses
- **Error Handling**: Proper error responses with retry information

## Expected Output

The demo will show:
```
🤖 AI-Powered Rate Limiter Demo

=== Demo 1: Basic Rate Limiting ===
✅ Request 1: ALLOWED (Remaining: 99)
✅ Request 2: ALLOWED (Remaining: 98)
...
❌ Request 101: BLOCKED (Remaining: 0)
   Retry after: 60 seconds

=== Demo 2: Burst Detection ===
🚀 Burst detected! Adaptive limits applied.
...

=== Demo 3: Multi-Endpoint Isolation ===
📊 Endpoint-specific limits working correctly.
...
```

## Troubleshooting

### Redis Connection Issues
```bash
# Check if Redis is running
redis-cli ping

# If using different Redis host/port, update the connection in demo files
$redis->connect('your-redis-host', your-redis-port);
```

### PHP Redis Extension
```bash
# Install Redis extension (Ubuntu/Debian)
sudo apt-get install php-redis

# Install Redis extension (CentOS/RHEL)
sudo yum install php-redis

# Install Redis extension (macOS)
pecl install redis
```

### Permission Issues
```bash
# Make sure you have write permissions for Redis
# Check Redis configuration for bind and protected-mode settings
``` 