# Response Caching

Cache API responses to reduce network calls, improve performance, and decrease server load.

## Basic Usage

### In-Memory Cache

Simple caching that stores responses in memory for the duration of the script.

```php
use Phpmystic\RetrofitPhp\Cache\InMemoryCache;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;

$cache = new InMemoryCache();
$cachePolicy = new CachePolicy(ttl: 300); // Cache for 5 minutes

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->cache($cache)
    ->cachePolicy($cachePolicy)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Cache Policy Options

Configure how caching behaves.

```php
$cachePolicy = new CachePolicy(
    ttl: 300,                    // Time-to-live in seconds (5 minutes)
    onlyGetRequests: true,       // Only cache GET requests (default: true)
    onlySuccessResponses: true,  // Only cache 2xx responses (default: true)
);
```

## Per-Method Caching

Override cache settings for specific endpoints using the `#[Cacheable]` attribute.

### Basic Per-Method Cache

```php
use Phpmystic\RetrofitPhp\Attributes\Cacheable;

interface UserApi
{
    #[GET('/users/{id}')]
    #[Cacheable(ttl: 60)]  // Cache for 1 minute
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/users')]
    #[Cacheable(ttl: 300)] // Cache for 5 minutes
    public function listUsers(): array;

    #[POST('/users')]
    public function createUser(#[Body] array $data): array; // Not cached
}
```

### Mixed Configuration

```php
interface ProductApi
{
    // Uses global cache policy (5 minutes)
    #[GET('/products')]
    public function listProducts(): array;

    // Override with 1 hour cache
    #[GET('/products/{id}')]
    #[Cacheable(ttl: 3600)]
    public function getProduct(#[Path('id')] int $id): array;

    // Cache for 10 minutes
    #[GET('/categories')]
    #[Cacheable(ttl: 600)]
    public function getCategories(): array;

    // POST not cached
    #[POST('/products')]
    public function createProduct(#[Body] array $product): array;
}
```

## Cache Behavior

### What Gets Cached

By default, only GET requests with successful responses (2xx status codes) are cached.

```php
// These are cached
#[GET('/users')]           // ✓ Cached
#[GET('/users/{id}')]      // ✓ Cached

// These are NOT cached by default
#[POST('/users')]          // ✗ Not GET
#[PUT('/users/{id}')]      // ✗ Not GET
#[DELETE('/users/{id}')]   // ✗ Not GET
```

### Cache Keys

Cache keys are generated from the request method and full URL (including query parameters).

```php
// Different cache keys:
$api->getUser(1);           // Key: GET|https://api.example.com/users/1
$api->getUser(2);           // Key: GET|https://api.example.com/users/2
$api->listUsers(page: 1);   // Key: GET|https://api.example.com/users?page=1
$api->listUsers(page: 2);   // Key: GET|https://api.example.com/users?page=2
```

### Cache Hits and Misses

```php
$api = $retrofit->create(UserApi::class);

// First call - Cache miss, makes HTTP request
$user = $api->getUser(1);  // ~200ms

// Second call - Cache hit, returns instantly
$user = $api->getUser(1);  // ~1ms

// Different parameter - Cache miss
$user = $api->getUser(2);  // ~200ms
```

## Cache Control Headers

Retrofit respects standard HTTP cache control headers.

### Request Headers

```php
// Request with Cache-Control: no-cache won't be cached
#[GET('/users/{id}')]
#[Headers('Cache-Control: no-cache')]
public function getUserFresh(#[Path('id')] int $id): array;
```

### Response Headers

```php
// Response with these headers won't be cached:
// - Cache-Control: no-store
// - Cache-Control: no-cache
// - Cache-Control: must-revalidate
```

## Custom Cache Implementation

Implement `CacheInterface` for persistent caching with Redis, Memcached, or filesystem.

### Redis Cache

```php
use Phpmystic\RetrofitPhp\Cache\CacheInterface;
use Phpmystic\RetrofitPhp\Http\Response;

class RedisCache implements CacheInterface
{
    public function __construct(private Redis $redis) {}

    public function get(string $key): ?Response
    {
        $data = $this->redis->get($key);
        return $data ? unserialize($data) : null;
    }

    public function set(string $key, Response $response, int $ttl): void
    {
        $this->redis->setex($key, $ttl, serialize($response));
    }

    public function invalidate(string $key): void
    {
        $this->redis->del($key);
    }

    public function clear(): void
    {
        $this->redis->flushDB();
    }
}

// Usage
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$cache = new RedisCache($redis);
$cachePolicy = new CachePolicy(ttl: 3600);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->cache($cache)
    ->cachePolicy($cachePolicy)
    ->build();
```

### Memcached Cache

```php
class MemcachedCache implements CacheInterface
{
    public function __construct(private Memcached $memcached) {}

    public function get(string $key): ?Response
    {
        $data = $this->memcached->get($key);
        return $data !== false ? unserialize($data) : null;
    }

    public function set(string $key, Response $response, int $ttl): void
    {
        $this->memcached->set($key, serialize($response), $ttl);
    }

    public function invalidate(string $key): void
    {
        $this->memcached->delete($key);
    }

    public function clear(): void
    {
        $this->memcached->flush();
    }
}

// Usage
$memcached = new Memcached();
$memcached->addServer('localhost', 11211);

$cache = new MemcachedCache($memcached);
```

### File System Cache

```php
class FileCache implements CacheInterface
{
    public function __construct(private string $cacheDir) {}

    public function get(string $key): ?Response
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));

        // Check expiration
        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return $data['response'];
    }

    public function set(string $key, Response $response, int $ttl): void
    {
        $file = $this->getCacheFile($key);
        $data = [
            'response' => $response,
            'expires_at' => time() + $ttl,
        ];

        file_put_contents($file, serialize($data));
    }

    public function invalidate(string $key): void
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}

// Usage
$cache = new FileCache('/tmp/retrofit-cache');
```

## Cache Management

### Manual Cache Invalidation

Invalidate specific cache entries.

```php
// Get cache instance from your DI container or create it
$cache = new InMemoryCache();

// Invalidate specific entry
$cacheKey = 'GET|https://api.example.com/users/1';
$cache->invalidate($cacheKey);
```

### Clear All Cache

Clear the entire cache.

```php
$cache->clear();
```

### Invalidate After Mutations

Invalidate related cache entries after creating/updating/deleting.

```php
class UserService
{
    public function __construct(
        private UserApi $api,
        private CacheInterface $cache
    ) {}

    public function updateUser(int $id, array $data): array
    {
        $result = $this->api->updateUser($id, $data);

        // Invalidate cached user data
        $this->cache->invalidate("GET|https://api.example.com/users/{$id}");
        $this->cache->invalidate("GET|https://api.example.com/users");

        return $result;
    }
}
```

## Advanced Patterns

### Two-Tier Caching

Combine memory and persistent cache.

```php
class TwoTierCache implements CacheInterface
{
    public function __construct(
        private InMemoryCache $l1Cache,
        private RedisCache $l2Cache
    ) {}

    public function get(string $key): ?Response
    {
        // Try L1 (memory) first
        $response = $this->l1Cache->get($key);
        if ($response !== null) {
            return $response;
        }

        // Try L2 (Redis)
        $response = $this->l2Cache->get($key);
        if ($response !== null) {
            // Promote to L1
            $this->l1Cache->set($key, $response, 300);
        }

        return $response;
    }

    public function set(string $key, Response $response, int $ttl): void
    {
        $this->l1Cache->set($key, $response, $ttl);
        $this->l2Cache->set($key, $response, $ttl);
    }

    // ... implement other methods
}
```

### Conditional Caching

Cache based on response content.

```php
class ConditionalCache implements CacheInterface
{
    public function __construct(private CacheInterface $cache) {}

    public function set(string $key, Response $response, int $ttl): void
    {
        // Only cache if response is not empty
        if (!empty($response->body)) {
            $this->cache->set($key, $response, $ttl);
        }
    }

    // ... delegate other methods to $cache
}
```

### Cache Statistics

Track cache hits and misses.

```php
class CacheWithStats implements CacheInterface
{
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(private CacheInterface $cache) {}

    public function get(string $key): ?Response
    {
        $response = $this->cache->get($key);

        if ($response !== null) {
            $this->hits++;
        } else {
            $this->misses++;
        }

        return $response;
    }

    public function getHitRate(): float
    {
        $total = $this->hits + $this->misses;
        return $total > 0 ? $this->hits / $total : 0.0;
    }

    // ... implement other methods
}
```

## Best Practices

### 1. Choose Appropriate TTL

Different data has different freshness requirements.

```php
#[GET('/users/{id}')]
#[Cacheable(ttl: 300)]        // User data: 5 minutes
public function getUser(int $id): array;

#[GET('/config')]
#[Cacheable(ttl: 3600)]       // Config: 1 hour
public function getConfig(): array;

#[GET('/news')]
#[Cacheable(ttl: 60)]         // News: 1 minute
public function getNews(): array;
```

### 2. Cache Only GET Requests

POST, PUT, DELETE should not be cached.

```php
// Good
#[GET('/users')]
#[Cacheable(ttl: 300)]
public function getUsers(): array;

// Bad - don't cache mutations
#[POST('/users')]
#[Cacheable(ttl: 300)]  // ❌ Don't do this
public function createUser(array $data): array;
```

### 3. Invalidate Related Caches

Clear related entries after mutations.

```php
// After updating user, invalidate:
// - GET /users/{id}
// - GET /users (list)
// - GET /users/{id}/profile
```

### 4. Use Persistent Cache for Production

In-memory cache is lost when the script ends. Use Redis/Memcached for production.

```php
// Development
$cache = new InMemoryCache();

// Production
$cache = new RedisCache($redis);
```

### 5. Monitor Cache Performance

Track hit rates to optimize TTL values.

```php
// If hit rate is low (< 50%), consider:
// - Increasing TTL
// - Reviewing cache key strategy
// - Checking if data changes too frequently
```

## Limitations

- Cache is instance-specific (not shared across PHP processes by default)
- No automatic cache warming
- No cache tags or smart invalidation
- Cannot cache streaming responses

## See Also

- [Interceptors](interceptors.md) - Implement custom caching logic
- [Retry Policies](retry.md) - Combine caching with retries
- [Examples](examples.md) - Complete caching examples
