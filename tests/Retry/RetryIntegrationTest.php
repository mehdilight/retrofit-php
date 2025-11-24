<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Retry;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;
use Phpmystic\RetrofitPhp\Retrofit;

interface TestApiWithRetry
{
    #[GET('/unstable-endpoint')]
    public function getUnstableData(): array;

    #[GET('/always-fails')]
    public function getAlwaysFails(): array;
}

class RetryIntegrationTest extends TestCase
{
    public function testRetrySucceedsAfterFailures(): void
    {
        $attemptCount = 0;

        // Mock client that fails twice, then succeeds
        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$attemptCount) {
                $attemptCount++;

                if ($attemptCount <= 2) {
                    return new Response(503, 'Service Unavailable', null, [], '');
                }

                return new Response(200, 'OK', null, [], '{"data": "success"}');
            });

        $retryPolicy = RetryPolicy::builder()
            ->maxAttempts(3)
            ->backoffStrategy(new ExponentialBackoff(baseDelayMs: 10)) // Short delay for testing
            ->build();

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->retryPolicy($retryPolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithRetry::class);
        $result = $api->getUnstableData();

        $this->assertEquals(['data' => 'success'], $result);
        $this->assertEquals(3, $attemptCount); // Failed twice, succeeded on third attempt
    }

    public function testRetryFailsAfterMaxAttempts(): void
    {
        $attemptCount = 0;

        // Mock client that always fails
        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$attemptCount) {
                $attemptCount++;
                return new Response(503, 'Service Unavailable', null, [], '');
            });

        $retryPolicy = RetryPolicy::builder()
            ->maxAttempts(3)
            ->backoffStrategy(new ExponentialBackoff(baseDelayMs: 10))
            ->build();

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->retryPolicy($retryPolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithRetry::class);

        try {
            $api->getAlwaysFails();
            $this->fail('Expected exception to be thrown');
        } catch (\RuntimeException $e) {
            // Expected to fail after max attempts
            $this->assertEquals(3, $attemptCount);
        }
    }

    public function testNoRetryOn400Error(): void
    {
        $attemptCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$attemptCount) {
                $attemptCount++;
                return new Response(400, 'Bad Request', null, [], '{"error": "invalid"}');
            });

        $retryPolicy = RetryPolicy::default();

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->retryPolicy($retryPolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithRetry::class);
        $result = $api->getUnstableData();

        // Should not retry on 4xx errors
        $this->assertEquals(1, $attemptCount);
        $this->assertEquals(['error' => 'invalid'], $result);
    }

    public function testRetryOnNetworkException(): void
    {
        $attemptCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$attemptCount) {
                $attemptCount++;

                if ($attemptCount <= 2) {
                    throw new \RuntimeException('Connection timeout');
                }

                return new Response(200, 'OK', null, [], '{"data": "success"}');
            });

        $retryPolicy = RetryPolicy::builder()
            ->maxAttempts(3)
            ->backoffStrategy(new ExponentialBackoff(baseDelayMs: 10))
            ->build();

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->retryPolicy($retryPolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithRetry::class);
        $result = $api->getUnstableData();

        $this->assertEquals(['data' => 'success'], $result);
        $this->assertEquals(3, $attemptCount);
    }

    public function testBackoffDelayIsApplied(): void
    {
        $attemptCount = 0;
        $attemptTimes = [];

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$attemptCount, &$attemptTimes) {
                $attemptTimes[] = microtime(true);
                $attemptCount++;

                if ($attemptCount <= 2) {
                    return new Response(503, 'Service Unavailable', null, [], '');
                }

                return new Response(200, 'OK', null, [], '{"data": "success"}');
            });

        $retryPolicy = RetryPolicy::builder()
            ->maxAttempts(3)
            ->backoffStrategy(new ExponentialBackoff(baseDelayMs: 100)) // 100ms base delay
            ->build();

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->retryPolicy($retryPolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithRetry::class);
        $result = $api->getUnstableData();

        $this->assertEquals(['data' => 'success'], $result);
        $this->assertEquals(3, $attemptCount);

        // Verify delays between attempts
        // First retry should wait ~100ms
        $delay1 = ($attemptTimes[1] - $attemptTimes[0]) * 1000;
        $this->assertGreaterThan(80, $delay1); // Allow some margin

        // Second retry should wait ~200ms
        $delay2 = ($attemptTimes[2] - $attemptTimes[1]) * 1000;
        $this->assertGreaterThan(150, $delay2);
    }
}
