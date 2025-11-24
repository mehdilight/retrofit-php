# Retrofit PHP

A type-safe HTTP client for PHP, inspired by [Square's Retrofit](https://square.github.io/retrofit/) for Java/Kotlin.

Turn your HTTP API into a PHP interface using attributes.

## Features

- ✅ **Type-Safe API Definitions** - Define APIs as PHP interfaces with attributes
- ✅ **HTTP Methods** - Support for GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS
- ✅ **URL Parameters** - Path parameters, query parameters, and query maps
- ✅ **Request Body** - JSON, form-encoded, and multipart requests
- ✅ **Headers** - Static and dynamic headers
- ✅ **Converters** - JSON conversion with custom DTO/model hydration
- ✅ **Async Requests** - Promise-based async operations with Guzzle
- ✅ **Retry Policies** - Automatic retries with exponential backoff
- ✅ **Response Caching** - TTL-based caching with pluggable cache backends
- ✅ **Interceptors** - Request/response modification and logging
- ✅ **Timeouts** - Per-endpoint timeout configuration
- ✅ **PSR-7 Compliant** - Request and Response objects implement PSR-7

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [HTTP Methods](#http-methods)
- [URL Parameters](#url-parameters)
- [Request Body](#request-body)
- [Headers](#headers)
- [HTTP Client Configuration](#http-client-configuration)
- [Converters](#converters)
- [Async Requests](#async-requests)
- [Retry Policies](#retry-policies)
- [Response Caching](#response-caching)
- [Timeouts](#timeouts)
- [Interceptors](#interceptors)
- [Complete Example](#complete-example)

## Installation

```bash
composer require phpmystic/retrofit-php
```

## Quick Start

### 1. Define your API interface

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

interface GitHubApi
{
    #[GET('/users/{user}')]
    public function getUser(#[Path('user')] string $username): array;

    #[GET('/users/{user}/repos')]
    public function listRepos(
        #[Path('user')] string $username,
        #[Query('per_page')] int $perPage = 10,
        #[Query('sort')] string $sort = 'updated'
    ): array;

    #[POST('/repos/{owner}/{repo}/issues')]
    public function createIssue(
        #[Path('owner')] string $owner,
        #[Path('repo')] string $repo,
        #[Body] array $issue
    ): array;
}
```

### 2. Create a Retrofit instance

```php
use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.github.com')
    ->client(GuzzleHttpClient::create(['timeout' => 30]))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### 3. Use your API

```php
$github = $retrofit->create(GitHubApi::class);

// GET request with path parameter
$user = $github->getUser('octocat');
echo $user['name']; // "The Octocat"

// GET request with query parameters
$repos = $github->listRepos('octocat', perPage: 5, sort: 'stars');

// POST request with body
$issue = $github->createIssue('owner', 'repo', [
    'title' => 'Bug report',
    'body' => 'Something is broken',
]);
```

## HTTP Methods

```php
#[GET('/users')]
public function getUsers(): array;

#[POST('/users')]
public function createUser(#[Body] array $data): array;

#[PUT('/users/{id}')]
public function updateUser(#[Path('id')] int $id, #[Body] array $data): array;

#[DELETE('/users/{id}')]
public function deleteUser(#[Path('id')] int $id): array;

#[PATCH('/users/{id}')]
public function patchUser(#[Path('id')] int $id, #[Body] array $data): array;

#[HEAD('/users/{id}')]
public function headUser(#[Path('id')] int $id): array;

#[OPTIONS('/users')]
public function optionsUsers(): array;
```

## URL Parameters

### Path Parameters

Replace URL segments dynamically:

```php
#[GET('/users/{user}/repos/{repo}')]
public function getRepo(
    #[Path('user')] string $user,
    #[Path('repo')] string $repo
): array;

// Calls: GET /users/octocat/repos/Hello-World
$repo = $api->getRepo('octocat', 'Hello-World');
```

### Query Parameters

Add query string parameters:

```php
#[GET('/users')]
public function searchUsers(
    #[Query('q')] string $query,
    #[Query('page')] int $page = 1,
    #[Query('per_page')] int $perPage = 10
): array;

// Calls: GET /users?q=john&page=2&per_page=20
$users = $api->searchUsers('john', page: 2, perPage: 20);
```

### Query Map

Pass multiple query parameters as an array:

```php
#[GET('/search')]
public function search(#[QueryMap] array $params): array;

// Calls: GET /search?q=test&sort=date&order=desc
$results = $api->search(['q' => 'test', 'sort' => 'date', 'order' => 'desc']);
```

## Request Body

### JSON Body

```php
#[POST('/users')]
public function createUser(#[Body] array $user): array;

$api->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
// Sends: {"name":"John Doe","email":"john@example.com"}
```

### Form Encoded

```php
use Phpmystic\RetrofitPhp\Attributes\FormUrlEncoded;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;

#[POST('/login')]
#[FormUrlEncoded]
public function login(
    #[Field('username')] string $username,
    #[Field('password')] string $password
): array;

// Sends: username=john&password=secret (application/x-www-form-urlencoded)
```

### Field Map

```php
#[POST('/login')]
#[FormUrlEncoded]
public function login(#[FieldMap] array $credentials): array;

$api->login(['username' => 'john', 'password' => 'secret']);
```

## Headers

### Static Headers

Define headers on the method:

```php
use Phpmystic\RetrofitPhp\Attributes\Headers;

#[GET('/users')]
#[Headers(
    'Accept: application/json',
    'X-Api-Version: v1'
)]
public function getUsers(): array;
```

### Dynamic Headers

Pass headers as parameters:

```php
use Phpmystic\RetrofitPhp\Attributes\Header;

#[GET('/users')]
public function getUsers(
    #[Header('Authorization')] string $token
): array;

$api->getUsers('Bearer my-token');
```

### Header Map

```php
use Phpmystic\RetrofitPhp\Attributes\HeaderMap;

#[GET('/users')]
public function getUsers(#[HeaderMap] array $headers): array;

$api->getUsers([
    'Authorization' => 'Bearer token',
    'X-Request-Id' => 'abc123',
]);
```

## HTTP Client Configuration

### Guzzle Options

```php
$client = GuzzleHttpClient::create([
    'timeout' => 30,
    'connect_timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
    ],
    'verify' => false, // Disable SSL verification (not recommended for production)
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Custom HTTP Client

Implement the `HttpClient` interface:

```php
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

class MyHttpClient implements HttpClient
{
    public function execute(Request $request): Response
    {
        // Your implementation
    }
}
```

## Converters

### JSON Converter (Default)

```php
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Custom Converter

Implement `ConverterFactory`:

```php
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

class XmlConverterFactory implements ConverterFactory
{
    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return new XmlRequestConverter();
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        return new XmlResponseConverter();
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return new StringConverter();
    }
}
```

## Async Requests

Execute requests asynchronously using Guzzle Promises.

### Using Call Object

```php
use GuzzleHttp\Promise\Utils;

// Get the internal proxy to access Call objects
$reflection = new ReflectionClass($retrofit);
$method = $reflection->getMethod('getServiceProxy');
$method->setAccessible(true);
$proxy = $method->invoke($retrofit, MyApi::class);

// Create Call and execute async
$call = $proxy->invoke('getUser', [1]);
$promise = $call->executeAsync();

// Wait for result
$response = $promise->wait();
echo $response->body['name'];
```

### Parallel Requests

```php
// Create multiple calls
$userCall = $proxy->invoke('getUser', [1]);
$postCall = $proxy->invoke('getPost', [1]);
$commentCall = $proxy->invoke('getComment', [1]);

// Execute all in parallel
$promises = [
    'user' => $userCall->executeAsync(),
    'post' => $postCall->executeAsync(),
    'comment' => $commentCall->executeAsync(),
];

// Wait for all to complete
$responses = Utils::unwrap($promises);

echo $responses['user']->body['name'];
echo $responses['post']->body['title'];
```

### With Callbacks

```php
$call = $proxy->invoke('getUser', [1]);

$promise = $call->executeAsync()
    ->then(function ($response) {
        echo "Got user: {$response->body['name']}\n";
        return $response;
    })
    ->otherwise(function ($exception) {
        echo "Error: {$exception->getMessage()}\n";
    });

$promise->wait();
```

### Batch Processing

```php
// Fetch 10 posts in parallel
$promises = [];
for ($i = 1; $i <= 10; $i++) {
    $call = $proxy->invoke('getPost', [$i]);
    $promises[] = $call->executeAsync();
}

$responses = Utils::unwrap($promises);

foreach ($responses as $response) {
    echo $response->body['title'] . "\n";
}
```

## Retry Policies

Automatically retry failed requests with configurable backoff strategies.

### Basic Retry

```php
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy(RetryPolicy::default()) // Retry on 429, 500, 502, 503, 504
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Custom Retry Policy

```php
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)                          // Total attempts (1 original + 4 retries)
    ->retryOnStatusCodes([429, 500, 503])     // Which status codes to retry
    ->retryOnExceptions([\RuntimeException::class]) // Which exceptions to retry
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 1000,     // Start with 1 second
        multiplier: 2.0,        // Double each time
        maxDelayMs: 30000,      // Cap at 30 seconds
        jitter: true            // Add randomization to prevent thundering herd
    ))
    ->build();

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy($retryPolicy)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Backoff Strategies

**Exponential Backoff** (recommended for most cases):
```php
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;

// Delays: 1s, 2s, 4s, 8s, 16s...
$backoff = new ExponentialBackoff(
    baseDelayMs: 1000,
    multiplier: 2.0,
    maxDelayMs: 60000,  // Optional: cap at 60 seconds
    jitter: true         // Optional: randomize delay
);
```

**Fixed Backoff**:
```php
use Phpmystic\RetrofitPhp\Retry\FixedBackoff;

// Always wait 5 seconds between retries
$backoff = new FixedBackoff(delayMs: 5000);
```

**Linear Backoff**:
```php
use Phpmystic\RetrofitPhp\Retry\LinearBackoff;

// Delays: 1s, 2s, 3s, 4s, 5s...
$backoff = new LinearBackoff(
    initialDelayMs: 1000,
    incrementMs: 1000,
    maxDelayMs: 10000  // Optional: cap at 10 seconds
);
```

### Disable Retries

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy(RetryPolicy::none())
    ->build();
```

## Response Caching

Cache responses to reduce API calls and improve performance.

### Basic Caching

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

```php
$cachePolicy = new CachePolicy(
    ttl: 300,                    // Time-to-live in seconds
    onlyGetRequests: true,       // Only cache GET requests (default: true)
    onlySuccessResponses: true,  // Only cache 2xx responses (default: true)
);
```

### Per-Method Caching

```php
use Phpmystic\RetrofitPhp\Attributes\Cacheable;

interface MyApi
{
    #[GET('/users/{id}')]
    #[Cacheable(ttl: 60)]  // Cache this endpoint for 1 minute
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/posts')]
    #[Cacheable(ttl: 300)] // Cache this endpoint for 5 minutes
    public function getPosts(): array;

    #[POST('/users')]
    public function createUser(#[Body] array $data): array; // Not cached
}
```

### Cache Control Headers

The cache policy respects standard HTTP cache headers:

```php
// Request with Cache-Control: no-cache won't be cached
// Response with Cache-Control: no-store won't be cached
```

### Custom Cache Implementation

Implement `CacheInterface` for custom caching (Redis, Memcached, etc.):

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
```

## Timeouts

Configure per-endpoint timeouts using the `#[Timeout]` attribute.

```php
use Phpmystic\RetrofitPhp\Attributes\Timeout;

interface MyApi
{
    #[GET('/quick')]
    #[Timeout(5)]  // 5 second timeout
    public function getQuickData(): array;

    #[GET('/slow')]
    #[Timeout(120)]  // 2 minute timeout
    public function getSlowData(): array;

    #[POST('/upload')]
    #[Timeout(300)]  // 5 minute timeout for file uploads
    public function uploadFile(#[Body] array $data): array;
}
```

**Note:** The `#[Timeout]` attribute is currently for documentation purposes. To configure timeouts, use Guzzle client options:

```php
$client = GuzzleHttpClient::create([
    'timeout' => 30,          // Default timeout for all requests
    'connect_timeout' => 10,  // Connection timeout
]);
```

## Interceptors

Intercept and modify requests/responses, add logging, authentication, etc.

### Creating an Interceptor

```php
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

class AuthInterceptor implements Interceptor
{
    public function __construct(private string $apiKey) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Add authentication header
        $newRequest = $request->withHeader('Authorization', "Bearer {$this->apiKey}");

        // Proceed with modified request
        return $chain->proceed($newRequest);
    }
}
```

### Adding Interceptors

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addInterceptor(new AuthInterceptor('my-api-key'))
    ->addInterceptor(new LoggingInterceptor())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Common Interceptor Use Cases

**Logging Interceptor**:
```php
class LoggingInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $start = microtime(true);

        echo "→ {$request->method} {$request->url}\n";

        $response = $chain->proceed($request);

        $duration = round((microtime(true) - $start) * 1000);
        echo "← {$response->code} ({$duration}ms)\n";

        return $response;
    }
}
```

**Retry-After Interceptor**:
```php
class RetryAfterInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $response = $chain->proceed($chain->request());

        // If rate limited, wait and retry
        if ($response->code === 429 && $response->hasHeader('Retry-After')) {
            $retryAfter = (int) $response->getHeaderLine('Retry-After');
            sleep($retryAfter);
            return $chain->proceed($chain->request());
        }

        return $response;
    }
}
```

**Cache Interceptor**:
```php
class CacheInterceptor implements Interceptor
{
    public function __construct(private CacheInterface $cache) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $cacheKey = md5($request->method . $request->url);

        // Check cache
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        // Execute request and cache
        $response = $chain->proceed($request);
        $this->cache->set($cacheKey, $response, 300);

        return $response;
    }
}
```

## Complete Example

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;
use Phpmystic\RetrofitPhp\Cache\InMemoryCache;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;
use Phpmystic\RetrofitPhp\Attributes\Cacheable;
use Phpmystic\RetrofitPhp\Attributes\Timeout;

interface PostsApi
{
    #[GET('/posts')]
    #[Cacheable(ttl: 300)]  // Cache for 5 minutes
    public function list(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts/{id}')]
    #[Cacheable(ttl: 60)]   // Cache for 1 minute
    #[Timeout(10)]
    public function get(#[Path('id')] int $id): array;

    #[POST('/posts')]
    public function create(#[Body] array $post): array;

    #[PUT('/posts/{id}')]
    public function update(#[Path('id')] int $id, #[Body] array $post): array;

    #[DELETE('/posts/{id}')]
    public function delete(#[Path('id')] int $id): array;
}

// Configure HTTP client
$client = GuzzleHttpClient::create([
    'timeout' => 30,
    'connect_timeout' => 10,
]);

// Configure retry policy
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(3)
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 1000,
        multiplier: 2.0
    ))
    ->build();

// Configure caching
$cache = new InMemoryCache();
$cachePolicy = new CachePolicy(ttl: 300);

// Build Retrofit instance
$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client($client)
    ->retryPolicy($retryPolicy)
    ->cache($cache)
    ->cachePolicy($cachePolicy)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(PostsApi::class);

// List posts (will be cached)
$posts = $api->list(5);
foreach ($posts as $post) {
    echo "- {$post['title']}\n";
}

// Get single post (will be cached)
$post = $api->get(1);
echo "Title: {$post['title']}\n";

// Second call returns from cache instantly
$post = $api->get(1);
echo "Cached title: {$post['title']}\n";

// Create post
$newPost = $api->create([
    'title' => 'My Post',
    'body' => 'Content here',
    'userId' => 1,
]);
echo "Created: {$newPost['id']}\n";

// Update post
$updated = $api->update(1, [
    'title' => 'Updated Title',
    'body' => 'Updated content',
    'userId' => 1,
]);

// Delete post
$api->delete(1);
```

## Requirements

- PHP 8.1+
- Guzzle 7.0+

## License

MIT
