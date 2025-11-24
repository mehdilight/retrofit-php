<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Response;

// ============================================
// Define Interceptors
// ============================================

/**
 * Authentication Interceptor
 * Adds Authorization header to every request
 */
class AuthInterceptor implements Interceptor
{
    public function __construct(
        private readonly string $token,
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request()->withHeader('Authorization', 'Bearer ' . $this->token);
        return $chain->proceed($request);
    }
}

/**
 * Logging Interceptor
 * Logs request and response information
 */
class LoggingInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $startTime = microtime(true);

        echo "[LOG] >> {$request->method} {$request->url}\n";

        $response = $chain->proceed($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "[LOG] << {$response->code} ({$duration}ms)\n";

        return $response;
    }
}

/**
 * Retry Interceptor
 * Retries failed requests up to a maximum number of times
 */
class RetryInterceptor implements Interceptor
{
    public function __construct(
        private readonly int $maxRetries = 3,
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $chain->proceed($request);

                // If successful (2xx), return immediately
                if ($response->isSuccessful()) {
                    return $response;
                }

                // If server error (5xx), retry
                if ($response->code >= 500 && $attempt < $this->maxRetries) {
                    echo "[RETRY] Attempt {$attempt} failed with {$response->code}, retrying...\n";
                    usleep(100000 * $attempt); // Exponential backoff
                    continue;
                }

                return $response;
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries) {
                    echo "[RETRY] Attempt {$attempt} threw exception, retrying...\n";
                    usleep(100000 * $attempt);
                }
            }
        }

        throw $lastException ?? new \RuntimeException('All retries failed');
    }
}

/**
 * Cache Interceptor
 * Caches GET responses in memory
 */
class CacheInterceptor implements Interceptor
{
    /** @var array<string, Response> */
    private array $cache = [];

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Only cache GET requests
        if ($request->method !== 'GET') {
            return $chain->proceed($request);
        }

        $cacheKey = $request->method . ':' . $request->url;

        // Return cached response if available
        if (isset($this->cache[$cacheKey])) {
            echo "[CACHE] HIT: {$cacheKey}\n";
            return $this->cache[$cacheKey];
        }

        echo "[CACHE] MISS: {$cacheKey}\n";
        $response = $chain->proceed($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            $this->cache[$cacheKey] = $response;
        }

        return $response;
    }
}

/**
 * Custom Headers Interceptor
 * Adds custom headers to every request
 */
class CustomHeadersInterceptor implements Interceptor
{
    public function __construct(
        /** @var array<string, string> */
        private readonly array $headers,
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request()->withHeaders($this->headers);
        return $chain->proceed($request);
    }
}

// ============================================
// Define API Interface
// ============================================

interface JsonPlaceholderApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/posts/{id}')]
    public function getPost(#[Path('id')] int $id): array;
}

// ============================================
// Create Retrofit with Interceptors
// ============================================

echo "=== Interceptors Example ===\n\n";

$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new TypedJsonConverterFactory())
    // Interceptors execute in order they're added
    ->addInterceptor(new LoggingInterceptor())
    ->addInterceptor(new CustomHeadersInterceptor([
        'X-App-Name' => 'RetrofitPHP',
        'X-App-Version' => '1.2.0',
    ]))
    ->addInterceptor(new CacheInterceptor())
    ->build();

/** @var JsonPlaceholderApi $api */
$api = $retrofit->create(JsonPlaceholderApi::class);

// ============================================
// Example 1: Basic Request with Logging
// ============================================
echo "1. Basic Request with Logging:\n";
$user = $api->getUser(1);
echo "   Got user: {$user['name']}\n\n";

// ============================================
// Example 2: Cache Hit
// ============================================
echo "2. Same Request (Cache Hit):\n";
$user = $api->getUser(1);
echo "   Got user: {$user['name']}\n\n";

// ============================================
// Example 3: Different Request (Cache Miss)
// ============================================
echo "3. Different Request (Cache Miss):\n";
$post = $api->getPost(1);
echo "   Got post: {$post['title']}\n\n";

// ============================================
// Example 4: With Auth Interceptor
// ============================================
echo "4. With Auth Interceptor:\n";

$retrofitWithAuth = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new TypedJsonConverterFactory())
    ->addInterceptor(new AuthInterceptor('my-secret-token'))
    ->addInterceptor(new LoggingInterceptor())
    ->build();

$apiWithAuth = $retrofitWithAuth->create(JsonPlaceholderApi::class);
$user = $apiWithAuth->getUser(2);
echo "   Got user: {$user['name']}\n\n";

echo "=== Done ===\n";
