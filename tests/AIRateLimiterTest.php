<?php

declare(strict_types=1);

namespace AhurSystem\AIRateLimiter\Tests;

use PHPUnit\Framework\TestCase;
use AhurSystem\AIRateLimiter\AIRateLimiter;
use AhurSystem\AIRateLimiter\RateLimitResult;
use Redis;

class AIRateLimiterTest extends TestCase
{
    private Redis $redis;
    private AIRateLimiter $limiter;
    
    protected function setUp(): void
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('Redis extension not available');
        }
        
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->flushAll(); // Clean slate for each test
        } catch (Exception $e) {
            $this->markTestSkipped('Redis connection failed: ' . $e->getMessage());
        }
        
        $this->limiter = new AIRateLimiter($this->redis, [
            'default_limit' => 10, // Use 10 for testing
            'default_window' => 60,
            'learning_enabled' => true,
            'adaptive_throttling' => true
        ]);
    }
    
    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->flushAll();
            $this->redis->close();
        }
    }
    
    public function testBasicRateLimiting(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        
        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertTrue($result->isAllowed());
        $this->assertEquals(9, $result->getRemainingRequests());
    }
    
    public function testRateLimitExceeded(): void
    {
        // Exceed the limit
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->check('user_123', 'api/v1/test');
        }
        
        $result = $this->limiter->check('user_123', 'api/v1/test');
        
        $this->assertFalse($result->isAllowed());
        $this->assertEquals(0, $result->getRemainingRequests());
        $this->assertGreaterThan(0, $result->getRetryDelay());
    }
    
    public function testDifferentEndpoints(): void
    {
        // Use up limit on one endpoint
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('user_123', 'api/v1/users');
        }
        
        // Check different endpoint should still be allowed
        $result = $this->limiter->check('user_123', 'api/v1/posts');
        
        $this->assertTrue($result->isAllowed());
        $this->assertEquals(9, $result->getRemainingRequests());
    }
    
    public function testBurstDetection(): void
    {
        // Simulate burst requests
        for ($i = 0; $i < 5; $i++) {
            $result = $this->limiter->check('user_123', 'api/v1/test', [
                'request_type' => 'burst'
            ]);
            
            // Burst factor should increase
            $this->assertGreaterThanOrEqual(1.0, $result->getBurstFactor());
        }
    }
    
    public function testAdaptiveLimiting(): void
    {
        // First request should have normal limits
        $result1 = $this->limiter->check('user_123', 'api/v1/test');
        $initialRemaining = $result1->getRemainingRequests();
        
        // Simulate consistent usage
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->check('user_123', 'api/v1/test');
        }
        
        // Check if adaptive limiting is working
        $result2 = $this->limiter->check('user_123', 'api/v1/test');
        
        // Should still be allowed, but with potentially different remaining count
        $this->assertTrue($result2->isAllowed());
    }
    
    public function testResetFunctionality(): void
    {
        // Use up some requests
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('user_123', 'api/v1/test');
        }
        
        $beforeReset = $this->limiter->check('user_123', 'api/v1/test');
        $this->assertEquals(4, $beforeReset->getRemainingRequests());
        
        // Reset the rate limit
        $this->limiter->reset('user_123', 'api/v1/test');
        
        $afterReset = $this->limiter->check('user_123', 'api/v1/test');
        $this->assertEquals(9, $afterReset->getRemainingRequests());
    }
    
    public function testConfiguration(): void
    {
        $config = $this->limiter->getConfig();
        
        $this->assertArrayHasKey('default_limit', $config);
        $this->assertArrayHasKey('default_window', $config);
        $this->assertArrayHasKey('learning_enabled', $config);
        $this->assertArrayHasKey('adaptive_throttling', $config);
    }
    
    public function testUpdateConfiguration(): void
    {
        $this->limiter->updateConfig(['default_limit' => 50]);
        $config = $this->limiter->getConfig();
        
        $this->assertEquals(50, $config['default_limit']);
    }
    
    public function testRateLimitResultMethods(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        
        $this->assertIsBool($result->isAllowed());
        $this->assertIsInt($result->getRemainingRequests());
        $this->assertIsInt($result->getRetryDelay());
        $this->assertInstanceOf(\DateTime::class, $result->getResetDateTime());
    }
    
    public function testJsonOutput(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $json = $result->toJson();
        
        $this->assertIsString($json);
        $this->assertJson($json);
        
        $data = json_decode($json, true);
        $this->assertArrayHasKey('allowed', $data);
        $this->assertArrayHasKey('remaining', $data);
    }
    
    public function testHeadersOutput(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $headers = $result->getHeaders();
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }
    
    public function testHeadersWhenRateLimited(): void
    {
        // Exceed the limit
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->check('user_123', 'api/v1/test');
        }
        
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $headers = $result->getHeaders();
        
        $this->assertArrayHasKey('Retry-After', $headers);
        $this->assertGreaterThan(0, $headers['Retry-After']);
    }
    
    public function testUsageStatistics(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $stats = $result->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('current_usage', $stats);
        $this->assertArrayHasKey('pattern_count', $stats);
        $this->assertArrayHasKey('trend', $stats);
        $this->assertArrayHasKey('burst_factor', $stats);
    }
    
    public function testMultiTenantIsolation(): void
    {
        // Use up limit for user1
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('user1', 'api/v1/test');
        }
        
        // Check user2 should still have full limit
        $result = $this->limiter->check('user2', 'api/v1/test');
        
        $this->assertTrue($result->isAllowed());
        $this->assertEquals(9, $result->getRemainingRequests());
    }
    
    public function testContextAwareness(): void
    {
        $result1 = $this->limiter->check('user_123', 'api/v1/test', [
            'user_agent' => 'Mozilla/5.0',
            'ip' => '192.168.1.100'
        ]);
        
        $result2 = $this->limiter->check('user_123', 'api/v1/test', [
            'user_agent' => 'Mobile/1.0',
            'ip' => '192.168.1.101'
        ]);
        
        $this->assertTrue($result1->isAllowed());
        $this->assertTrue($result2->isAllowed());
    }
    
    public function testLearningDisabled(): void
    {
        $limiter = new AIRateLimiter($this->redis, [
            'default_limit' => 100,
            'learning_enabled' => false,
            'adaptive_throttling' => false
        ]);
        
        $result = $limiter->check('user_123', 'api/v1/test');
        
        $this->assertTrue($result->isAllowed());
        $this->assertEquals(99, $result->getRemainingRequests());
    }
    
    public function testExponentialRetryStrategy(): void
    {
        // Exceed the limit
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->check('user_123', 'api/v1/test');
        }
        
        $result1 = $this->limiter->check('user_123', 'api/v1/test');
        $delay1 = $result1->getRetryDelay();
        
        // Should be blocked again
        $result2 = $this->limiter->check('user_123', 'api/v1/test');
        $delay2 = $result2->getRetryDelay();
        
        // Exponential backoff should increase delay
        $this->assertGreaterThan($delay1, $delay2);
    }
    
    public function testResetTimeCalculation(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $resetTime = $result->getResetDateTime();
        
        $this->assertInstanceOf(\DateTime::class, $resetTime);
        $this->assertGreaterThan(time(), $resetTime->getTimestamp());
    }
    
    public function testDateTimeFormatting(): void
    {
        $result = $this->limiter->check('user_123', 'api/v1/test');
        $resetTime = $result->getResetDateTime();
        
        $formatted = $resetTime->format('Y-m-d H:i:s');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $formatted);
    }
} 