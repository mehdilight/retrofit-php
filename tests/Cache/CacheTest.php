<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Cache\InMemoryCache;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

class CacheTest extends TestCase
{
    public function testInMemoryCacheStoresAndRetrieves(): void
    {
        $cache = new InMemoryCache();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(200, 'OK', ['id' => 1], [], '{"id":1}');

        $cacheKey = 'test_key';
        $cache->set($cacheKey, $response, ttl: 60);

        $cached = $cache->get($cacheKey);
        $this->assertNotNull($cached);
        $this->assertEquals(200, $cached->code);
        $this->assertEquals(['id' => 1], $cached->body);
    }

    public function testCacheExpiration(): void
    {
        $cache = new InMemoryCache();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(200, 'OK', ['id' => 1], [], '{"id":1}');

        $cacheKey = 'test_key';
        $cache->set($cacheKey, $response, ttl: 1); // 1 second TTL

        // Should be cached immediately
        $this->assertNotNull($cache->get($cacheKey));

        // Wait for expiration
        sleep(2);

        // Should be expired
        $this->assertNull($cache->get($cacheKey));
    }

    public function testCacheInvalidation(): void
    {
        $cache = new InMemoryCache();
        $request = new Request('GET', 'https://api.example.com/users');
        $response = new Response(200, 'OK', ['id' => 1], [], '{"id":1}');

        $cacheKey = 'test_key';
        $cache->set($cacheKey, $response, ttl: 60);

        $this->assertNotNull($cache->get($cacheKey));

        $cache->invalidate($cacheKey);

        $this->assertNull($cache->get($cacheKey));
    }

    public function testCacheClear(): void
    {
        $cache = new InMemoryCache();

        $cache->set('key1', new Response(200, 'OK', null), ttl: 60);
        $cache->set('key2', new Response(200, 'OK', null), ttl: 60);

        $this->assertNotNull($cache->get('key1'));
        $this->assertNotNull($cache->get('key2'));

        $cache->clear();

        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
    }

    public function testCachePolicyGeneratesConsistentKeys(): void
    {
        $policy = new CachePolicy(ttl: 60);

        $request1 = new Request('GET', 'https://api.example.com/users', [], ['page' => 1]);
        $request2 = new Request('GET', 'https://api.example.com/users', [], ['page' => 1]);
        $request3 = new Request('GET', 'https://api.example.com/users', [], ['page' => 2]);

        $key1 = $policy->generateKey($request1);
        $key2 = $policy->generateKey($request2);
        $key3 = $policy->generateKey($request3);

        // Same request should generate same key
        $this->assertEquals($key1, $key2);

        // Different request should generate different key
        $this->assertNotEquals($key1, $key3);
    }

    public function testCachePolicyRespectsCacheControl(): void
    {
        $policy = new CachePolicy(ttl: 60);

        // Request with no-cache should not be cacheable
        $request = new Request('GET', 'https://api.example.com/users', ['Cache-Control' => 'no-cache']);
        $this->assertFalse($policy->isCacheable($request, null));

        // Response with no-store should not be cacheable
        $response = new Response(200, 'OK', null, ['Cache-Control' => 'no-store']);
        $this->assertFalse($policy->isCacheable(
            new Request('GET', 'https://api.example.com/users'),
            $response
        ));

        // POST request should not be cacheable by default
        $postRequest = new Request('POST', 'https://api.example.com/users', [], [], ['data' => 'test']);
        $this->assertFalse($policy->isCacheable($postRequest, null));

        // GET request with 200 response should be cacheable
        $getRequest = new Request('GET', 'https://api.example.com/users');
        $okResponse = new Response(200, 'OK', null);
        $this->assertTrue($policy->isCacheable($getRequest, $okResponse));
    }

    public function testCachePolicyWithCustomTTL(): void
    {
        $policy = new CachePolicy(ttl: 120);
        $this->assertEquals(120, $policy->getTtl());

        $policy2 = new CachePolicy(ttl: 300);
        $this->assertEquals(300, $policy2->getTtl());
    }

    public function testCachePolicyOnlyGetRequests(): void
    {
        $policy = new CachePolicy(ttl: 60, onlyGetRequests: true);

        $getRequest = new Request('GET', 'https://api.example.com/users');
        $response = new Response(200, 'OK', null);
        $this->assertTrue($policy->isCacheable($getRequest, $response));

        $postRequest = new Request('POST', 'https://api.example.com/users', [], [], ['data' => 'test']);
        $this->assertFalse($policy->isCacheable($postRequest, $response));

        $putRequest = new Request('PUT', 'https://api.example.com/users/1', [], [], ['data' => 'test']);
        $this->assertFalse($policy->isCacheable($putRequest, $response));
    }

    public function testCachePolicyOnlySuccessResponses(): void
    {
        $policy = new CachePolicy(ttl: 60, onlySuccessResponses: true);
        $request = new Request('GET', 'https://api.example.com/users');

        $response200 = new Response(200, 'OK', null);
        $this->assertTrue($policy->isCacheable($request, $response200));

        $response201 = new Response(201, 'Created', null);
        $this->assertTrue($policy->isCacheable($request, $response201));

        $response404 = new Response(404, 'Not Found', null);
        $this->assertFalse($policy->isCacheable($request, $response404));

        $response500 = new Response(500, 'Internal Server Error', null);
        $this->assertFalse($policy->isCacheable($request, $response500));
    }
}
