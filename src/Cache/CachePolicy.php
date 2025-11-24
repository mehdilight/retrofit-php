<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Cache;

use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

final class CachePolicy
{
    public function __construct(
        private readonly int $ttl = 60,
        private readonly bool $onlyGetRequests = true,
        private readonly bool $onlySuccessResponses = true,
    ) {}

    /**
     * Determine if a request/response should be cached.
     */
    public function isCacheable(Request $request, ?Response $response): bool
    {
        // Check if request has no-cache header
        if ($request->hasHeader('Cache-Control')) {
            $cacheControl = $request->getHeaderLine('Cache-Control');
            if (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store')) {
                return false;
            }
        }

        // Check if response has no-store header
        if ($response !== null && $response->hasHeader('Cache-Control')) {
            $cacheControl = $response->getHeaderLine('Cache-Control');
            if (str_contains($cacheControl, 'no-store')) {
                return false;
            }
        }

        // Check if only GET requests should be cached
        if ($this->onlyGetRequests && $request->method !== 'GET') {
            return false;
        }

        // Check if only success responses should be cached
        if ($this->onlySuccessResponses && $response !== null && !$response->isSuccessful()) {
            return false;
        }

        return true;
    }

    /**
     * Generate a cache key for the request.
     */
    public function generateKey(Request $request): string
    {
        // Use the full URI which includes the path and query parameters
        $uri = (string) $request->getUri();

        // Include method and full URI in the cache key
        $parts = [
            $request->method,
            $uri,
        ];

        return md5(implode(':', $parts));
    }

    /**
     * Get the TTL in seconds.
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }
}
