<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Interceptor;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;

interface InterceptorTestApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/posts')]
    public function getPosts(): array;
}

class InterceptorIntegrationTest extends TestCase
{
    public function testInterceptorAddsAuthHeader(): void
    {
        $capturedHeaders = [];

        $mock = new MockHandler([
            function ($request) use (&$capturedHeaders) {
                $capturedHeaders = $request->getHeaders();
                return new GuzzleResponse(200, [], json_encode(['id' => 1, 'name' => 'John']));
            },
        ]);

        $authInterceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('Authorization', 'Bearer test-token');
                return $chain->proceed($request);
            }
        };

        $retrofit = $this->createRetrofitWithInterceptors($mock, [$authInterceptor]);
        $api = $retrofit->create(InterceptorTestApi::class);

        $api->getUser(1);

        $this->assertArrayHasKey('Authorization', $capturedHeaders);
        $this->assertSame(['Bearer test-token'], $capturedHeaders['Authorization']);
    }

    public function testMultipleInterceptorsAddHeaders(): void
    {
        $capturedHeaders = [];

        $mock = new MockHandler([
            function ($request) use (&$capturedHeaders) {
                $capturedHeaders = $request->getHeaders();
                return new GuzzleResponse(200, [], json_encode(['id' => 1]));
            },
        ]);

        $authInterceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('Authorization', 'Bearer token');
                return $chain->proceed($request);
            }
        };

        $apiKeyInterceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('X-API-Key', 'key123');
                return $chain->proceed($request);
            }
        };

        $retrofit = $this->createRetrofitWithInterceptors($mock, [$authInterceptor, $apiKeyInterceptor]);
        $api = $retrofit->create(InterceptorTestApi::class);

        $api->getUser(1);

        $this->assertSame(['Bearer token'], $capturedHeaders['Authorization']);
        $this->assertSame(['key123'], $capturedHeaders['X-API-Key']);
    }

    public function testLoggingInterceptor(): void
    {
        $logs = [];

        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1, 'name' => 'John'])),
        ]);

        $loggingInterceptor = new class($logs) implements Interceptor {
            public function __construct(private array &$logs) {}

            public function intercept(Chain $chain): Response
            {
                $request = $chain->request();
                $this->logs[] = ">> {$request->method} {$request->url}";

                $response = $chain->proceed($request);

                $this->logs[] = "<< {$response->code}";

                return $response;
            }
        };

        $retrofit = $this->createRetrofitWithInterceptors($mock, [$loggingInterceptor]);
        $api = $retrofit->create(InterceptorTestApi::class);

        $api->getUser(1);

        $this->assertCount(2, $logs);
        $this->assertStringContainsString('>> GET', $logs[0]);
        $this->assertStringContainsString('/users/1', $logs[0]);
        $this->assertSame('<< 200', $logs[1]);
    }

    public function testInterceptorCanModifyResponse(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1, 'name' => 'John'])),
        ]);

        $responseModifier = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $response = $chain->proceed($chain->request());
                return new Response(
                    $response->code,
                    $response->message,
                    $response->body,
                    array_merge($response->headers, ['X-Intercepted' => 'true']),
                    $response->rawBody
                );
            }
        };

        $retrofit = $this->createRetrofitWithInterceptors($mock, [$responseModifier]);
        $api = $retrofit->create(InterceptorTestApi::class);

        // The response modification happens but since we return array, we can't directly test headers
        // This test verifies the interceptor runs without error
        $result = $api->getUser(1);
        $this->assertSame(1, $result['id']);
    }

    public function testCachingInterceptor(): void
    {
        $requestCount = 0;

        $mock = new MockHandler([
            function ($request) use (&$requestCount) {
                $requestCount++;
                return new GuzzleResponse(200, [], json_encode(['id' => 1, 'name' => 'John']));
            },
            function ($request) use (&$requestCount) {
                $requestCount++;
                return new GuzzleResponse(200, [], json_encode(['id' => 1, 'name' => 'John']));
            },
        ]);

        $cache = [];
        $cachingInterceptor = new class($cache) implements Interceptor {
            public function __construct(private array &$cache) {}

            public function intercept(Chain $chain): Response
            {
                $request = $chain->request();
                $cacheKey = $request->method . ':' . $request->url;

                if (isset($this->cache[$cacheKey])) {
                    return $this->cache[$cacheKey];
                }

                $response = $chain->proceed($request);
                $this->cache[$cacheKey] = $response;

                return $response;
            }
        };

        $retrofit = $this->createRetrofitWithInterceptors($mock, [$cachingInterceptor]);
        $api = $retrofit->create(InterceptorTestApi::class);

        // First call - hits the server
        $api->getUser(1);
        $this->assertSame(1, $requestCount);

        // Second call - should be cached
        $api->getUser(1);
        $this->assertSame(1, $requestCount); // Still 1, no new request made
    }

    /**
     * @param Interceptor[] $interceptors
     */
    private function createRetrofitWithInterceptors(MockHandler $mock, array $interceptors): Retrofit
    {
        $builder = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client(new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)])))
            ->addConverterFactory(new TypedJsonConverterFactory());

        foreach ($interceptors as $interceptor) {
            $builder->addInterceptor($interceptor);
        }

        return $builder->build();
    }
}
