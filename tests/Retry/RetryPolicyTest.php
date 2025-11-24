<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Retry;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;

class RetryPolicyTest extends TestCase
{
    public function testShouldRetryOn503(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(503, 'Service Unavailable', null);

        $this->assertTrue($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldRetryOn502(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(502, 'Bad Gateway', null);

        $this->assertTrue($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldRetryOn429RateLimited(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(429, 'Too Many Requests', null);

        $this->assertTrue($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldNotRetryOn400(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(400, 'Bad Request', null);

        $this->assertFalse($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldNotRetryOn404(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(404, 'Not Found', null);

        $this->assertFalse($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldNotRetryOn200Success(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(200, 'OK', null);

        $this->assertFalse($policy->shouldRetry($request, $response, null, 0));
    }

    public function testShouldRetryOnNetworkException(): void
    {
        $policy = RetryPolicy::default();
        $request = new Request('GET', 'https://api.example.com/users');
        $exception = new \RuntimeException('Connection timeout');

        $this->assertTrue($policy->shouldRetry($request, null, $exception, 0));
    }

    public function testShouldNotRetryAfterMaxAttempts(): void
    {
        $policy = RetryPolicy::builder()
            ->maxAttempts(3)
            ->build();

        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(503, 'Service Unavailable', null);

        // Attempt 0, 1, 2 should retry (total 3 attempts)
        $this->assertTrue($policy->shouldRetry($request, $response, null, 0));
        $this->assertTrue($policy->shouldRetry($request, $response, null, 1));
        $this->assertFalse($policy->shouldRetry($request, $response, null, 2)); // 3rd attempt, no more retries
    }

    public function testCustomRetryableStatusCodes(): void
    {
        $policy = RetryPolicy::builder()
            ->retryOnStatusCodes([500, 503])
            ->build();

        $request = new Request('GET', 'https://api.example.com/users');

        $response500 = new Response(500, 'Internal Server Error', null);
        $this->assertTrue($policy->shouldRetry($request, $response500, null, 0));

        $response503 = new Response(503, 'Service Unavailable', null);
        $this->assertTrue($policy->shouldRetry($request, $response503, null, 0));

        $response502 = new Response(502, 'Bad Gateway', null);
        $this->assertFalse($policy->shouldRetry($request, $response502, null, 0));
    }

    public function testRetryOnlyOnSpecificExceptions(): void
    {
        $policy = RetryPolicy::builder()
            ->retryOnExceptions([\RuntimeException::class])
            ->build();

        $request = new Request('GET', 'https://api.example.com/users');

        $runtimeException = new \RuntimeException('Network error');
        $this->assertTrue($policy->shouldRetry($request, null, $runtimeException, 0));

        $logicException = new \LogicException('Logic error');
        $this->assertFalse($policy->shouldRetry($request, null, $logicException, 0));
    }

    public function testNoRetryPolicy(): void
    {
        $policy = RetryPolicy::none();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(503, 'Service Unavailable', null);

        $this->assertFalse($policy->shouldRetry($request, $response, null, 0));
    }
}
