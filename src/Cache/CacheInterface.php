<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Cache;

use Phpmystic\RetrofitPhp\Http\Response;

interface CacheInterface
{
    /**
     * Get a cached response.
     *
     * @param string $key Cache key
     * @return Response|null The cached response or null if not found or expired
     */
    public function get(string $key): ?Response;

    /**
     * Store a response in the cache.
     *
     * @param string $key Cache key
     * @param Response $response The response to cache
     * @param int $ttl Time-to-live in seconds
     */
    public function set(string $key, Response $response, int $ttl): void;

    /**
     * Invalidate a cached response.
     *
     * @param string $key Cache key
     */
    public function invalidate(string $key): void;

    /**
     * Clear all cached responses.
     */
    public function clear(): void;
}
