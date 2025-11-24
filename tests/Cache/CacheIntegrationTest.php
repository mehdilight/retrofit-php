<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Cacheable;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Cache\InMemoryCache;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;

interface TestApiWithCache
{
    #[GET('/users/{id}')]
    #[Cacheable(ttl: 60)]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/posts')]
    #[Cacheable(ttl: 120)]
    public function getPosts(): array;

    #[POST('/users')]
    public function createUser(array $data): array;

    #[GET('/fresh-data')]
    public function getFreshData(): array;
}

class CacheIntegrationTest extends TestCase
{
    public function testCachedRequestReturnsFromCache(): void
    {
        $callCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$callCount) {
                $callCount++;
                return new Response(200, 'OK', null, [], '{"id":1,"name":"John"}');
            });

        $cache = new InMemoryCache();
        $cachePolicy = new CachePolicy(ttl: 60);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->cache($cache)
            ->cachePolicy($cachePolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithCache::class);

        // First call - should hit the API
        $result1 = $api->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result1);
        $this->assertEquals(1, $callCount);

        // Second call - should return from cache
        $result2 = $api->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result2);
        $this->assertEquals(1, $callCount); // Call count should not increase

        // Third call - should still return from cache
        $result3 = $api->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result3);
        $this->assertEquals(1, $callCount);
    }

    public function testDifferentRequestsAreCachedSeparately(): void
    {
        $callCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$callCount) {
                $callCount++;

                if (str_contains($request->url, '/users/1')) {
                    return new Response(200, 'OK', null, [], '{"id":1,"name":"John"}');
                } else {
                    return new Response(200, 'OK', null, [], '{"id":2,"name":"Jane"}');
                }
            });

        $cache = new InMemoryCache();
        $cachePolicy = new CachePolicy(ttl: 60);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->cache($cache)
            ->cachePolicy($cachePolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithCache::class);

        // Call for user 1
        $result1 = $api->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result1);
        $this->assertEquals(1, $callCount);

        // Call for user 2 - should hit API
        $result2 = $api->getUser(2);
        $this->assertEquals(['id' => 2, 'name' => 'Jane'], $result2);
        $this->assertEquals(2, $callCount);

        // Call for user 1 again - should return from cache
        $result3 = $api->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result3);
        $this->assertEquals(2, $callCount); // Should not increase
    }

    public function testPostRequestsAreNotCached(): void
    {
        $callCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$callCount) {
                $callCount++;
                return new Response(201, 'Created', null, [], '{"id":1,"name":"John"}');
            });

        $cache = new InMemoryCache();
        $cachePolicy = new CachePolicy(ttl: 60, onlyGetRequests: true);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->cache($cache)
            ->cachePolicy($cachePolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithCache::class);

        // POST requests should not be cached
        $api->createUser(['name' => 'John']);
        $this->assertEquals(1, $callCount);

        $api->createUser(['name' => 'John']);
        $this->assertEquals(2, $callCount); // Should call API again
    }

    public function testMethodWithoutCacheableAttribute(): void
    {
        $callCount = 0;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$callCount) {
                $callCount++;
                return new Response(200, 'OK', null, [], '{"data":"fresh"}');
            });

        $cache = new InMemoryCache();
        $cachePolicy = new CachePolicy(ttl: 60);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->cache($cache)
            ->cachePolicy($cachePolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithCache::class);

        // Method without Cacheable attribute should still use global cache policy
        $api->getFreshData();
        $this->assertEquals(1, $callCount);

        $api->getFreshData();
        // With global cache enabled, it should be cached
        $this->assertEquals(1, $callCount);
    }

    public function testCacheExpirationRefetchesData(): void
    {
        $callCount = 0;
        $responseData = 'first';

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$callCount, &$responseData) {
                $callCount++;
                return new Response(200, 'OK', null, [], '{"data":"' . $responseData . '"}');
            });

        $cache = new InMemoryCache();
        $cachePolicy = new CachePolicy(ttl: 1); // 1 second TTL

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->cache($cache)
            ->cachePolicy($cachePolicy)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(TestApiWithCache::class);

        // First call
        $result1 = $api->getUser(1);
        $this->assertEquals(['data' => 'first'], $result1);
        $this->assertEquals(1, $callCount);

        // Second call - should be cached
        $result2 = $api->getUser(1);
        $this->assertEquals(['data' => 'first'], $result2);
        $this->assertEquals(1, $callCount);

        // Wait for cache to expire
        sleep(2);

        // Change the response data
        $responseData = 'second';

        // Third call - cache expired, should fetch new data
        $result3 = $api->getUser(1);
        $this->assertEquals(['data' => 'second'], $result3);
        $this->assertEquals(2, $callCount);
    }
}
