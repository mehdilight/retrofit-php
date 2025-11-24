<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Cache;

use Phpmystic\RetrofitPhp\Http\Response;

final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, array{response: Response, expiry: int}>
     */
    private array $cache = [];

    public function get(string $key): ?Response
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $entry = $this->cache[$key];

        // Check if expired
        if (time() > $entry['expiry']) {
            unset($this->cache[$key]);
            return null;
        }

        return $entry['response'];
    }

    public function set(string $key, Response $response, int $ttl): void
    {
        $this->cache[$key] = [
            'response' => $response,
            'expiry' => time() + $ttl,
        ];
    }

    public function invalidate(string $key): void
    {
        unset($this->cache[$key]);
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
