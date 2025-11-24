# Interceptors

Interceptors allow you to intercept and modify requests and responses, enabling features like authentication, logging, error handling, and custom behavior.

## Basic Interceptor

Create an interceptor by implementing the `Interceptor` interface.

### Simple Example

```php
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Http\Response;

class SimpleInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Modify request here

        $response = $chain->proceed($request);

        // Modify response here

        return $response;
    }
}
```

### Adding Interceptors

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addInterceptor(new SimpleInterceptor())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

## Common Use Cases

### Authentication Interceptor

Automatically add authentication headers to all requests.

```php
class AuthInterceptor implements Interceptor
{
    public function __construct(private string $apiKey) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Add authentication header
        $newRequest = $request->withHeader('Authorization', "Bearer {$this->apiKey}");

        return $chain->proceed($newRequest);
    }
}

// Usage
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addInterceptor(new AuthInterceptor('my-api-key'))
    ->build();
```

### Logging Interceptor

Log all requests and responses for debugging.

```php
class LoggingInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $start = microtime(true);

        echo "→ {$request->method} {$request->url}\n";

        if (!empty($request->headers)) {
            echo "  Headers: " . json_encode($request->headers) . "\n";
        }

        if ($request->body) {
            echo "  Body: {$request->body}\n";
        }

        $response = $chain->proceed($request);

        $duration = round((microtime(true) - $start) * 1000);
        echo "← {$response->code} ({$duration}ms)\n";

        if (!empty($response->body)) {
            $preview = substr(json_encode($response->body), 0, 100);
            echo "  Body: {$preview}...\n";
        }

        return $response;
    }
}
```

### Error Handler Interceptor

Transform error responses into exceptions.

```php
class ErrorHandlerInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $response = $chain->proceed($chain->request());

        // Handle different error codes
        if ($response->code >= 400) {
            $message = $response->body['message'] ?? 'Unknown error';

            throw match(true) {
                $response->code === 401 => new UnauthorizedException($message),
                $response->code === 403 => new ForbiddenException($message),
                $response->code === 404 => new NotFoundException($message),
                $response->code >= 500 => new ServerException($message),
                default => new ApiException($message, $response->code),
            };
        }

        return $response;
    }
}
```

### Retry-After Interceptor

Handle rate limiting with Retry-After header.

```php
class RetryAfterInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $response = $chain->proceed($chain->request());

        // If rate limited, wait and retry
        if ($response->code === 429 && $response->hasHeader('Retry-After')) {
            $retryAfter = (int) $response->getHeaderLine('Retry-After');

            echo "Rate limited. Waiting {$retryAfter} seconds...\n";
            sleep($retryAfter);

            // Retry the request
            return $chain->proceed($chain->request());
        }

        return $response;
    }
}
```

### Request ID Interceptor

Add unique request IDs for tracing.

```php
class RequestIdInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Generate unique request ID
        $requestId = uniqid('req-', true);

        // Add to request headers
        $newRequest = $request->withHeader('X-Request-Id', $requestId);

        $response = $chain->proceed($newRequest);

        // Log the request ID with response
        error_log("Request {$requestId}: {$response->code}");

        return $response;
    }
}
```

### User Agent Interceptor

Set custom User-Agent for all requests.

```php
class UserAgentInterceptor implements Interceptor
{
    public function __construct(
        private string $appName,
        private string $version
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        $userAgent = "{$this->appName}/{$this->version} PHP/" . PHP_VERSION;
        $newRequest = $request->withHeader('User-Agent', $userAgent);

        return $chain->proceed($newRequest);
    }
}
```

## Advanced Interceptors

### Conditional Interceptor

Apply logic conditionally based on request.

```php
class ConditionalAuthInterceptor implements Interceptor
{
    public function __construct(
        private string $publicKey,
        private string $privateKey
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Only add auth for specific endpoints
        if (str_contains($request->url, '/api/private/')) {
            $request = $request->withHeader('X-Api-Key', $this->privateKey);
        } else {
            $request = $request->withHeader('X-Api-Key', $this->publicKey);
        }

        return $chain->proceed($request);
    }
}
```

### Response Transformation Interceptor

Transform responses before they reach the application.

```php
class ResponseTransformInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $response = $chain->proceed($chain->request());

        // Unwrap nested responses
        if (isset($response->body['data'])) {
            $response->body = $response->body['data'];
        }

        // Add metadata
        $response->body['_fetched_at'] = time();

        return $response;
    }
}
```

### Performance Monitoring Interceptor

Track request performance metrics.

```php
class PerformanceInterceptor implements Interceptor
{
    private array $metrics = [];

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $start = microtime(true);

        $response = $chain->proceed($request);

        $duration = microtime(true) - $start;

        $this->metrics[] = [
            'url' => $request->url,
            'method' => $request->method,
            'status' => $response->code,
            'duration' => $duration,
            'timestamp' => time(),
        ];

        // Log slow requests
        if ($duration > 1.0) {
            error_log("Slow request: {$request->method} {$request->url} ({$duration}s)");
        }

        return $response;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getAverageDuration(): float
    {
        if (empty($this->metrics)) {
            return 0.0;
        }

        $total = array_sum(array_column($this->metrics, 'duration'));
        return $total / count($this->metrics);
    }
}
```

### Caching Interceptor

Custom caching logic.

```php
class CacheInterceptor implements Interceptor
{
    public function __construct(private CacheInterface $cache) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Only cache GET requests
        if ($request->method !== 'GET') {
            return $chain->proceed($request);
        }

        $cacheKey = md5($request->method . $request->url);

        // Check cache
        if ($cached = $this->cache->get($cacheKey)) {
            echo "Cache hit: {$request->url}\n";
            return $cached;
        }

        // Execute request
        $response = $chain->proceed($request);

        // Cache successful responses
        if ($response->code >= 200 && $response->code < 300) {
            $this->cache->set($cacheKey, $response, 300);
        }

        return $response;
    }
}
```

### Header Validation Interceptor

Validate required headers.

```php
class HeaderValidationInterceptor implements Interceptor
{
    public function __construct(private array $requiredHeaders) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Validate required headers are present
        foreach ($this->requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                throw new \InvalidArgumentException(
                    "Required header '{$header}' is missing"
                );
            }
        }

        return $chain->proceed($request);
    }
}

// Usage
$interceptor = new HeaderValidationInterceptor([
    'Authorization',
    'X-Api-Version'
]);
```

## Multiple Interceptors

Chain multiple interceptors together. They execute in the order added.

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addInterceptor(new UserAgentInterceptor('MyApp', '1.0'))
    ->addInterceptor(new AuthInterceptor('api-key'))
    ->addInterceptor(new RequestIdInterceptor())
    ->addInterceptor(new LoggingInterceptor())
    ->addInterceptor(new ErrorHandlerInterceptor())
    ->build();

// Execution order:
// 1. UserAgentInterceptor (adds User-Agent)
// 2. AuthInterceptor (adds Authorization)
// 3. RequestIdInterceptor (adds X-Request-Id)
// 4. LoggingInterceptor (logs request)
// 5. ErrorHandlerInterceptor (handles errors)
// 6. HTTP request is sent
// 7. ErrorHandlerInterceptor (handles response errors)
// 8. LoggingInterceptor (logs response)
// 9. RequestIdInterceptor (processes response)
// 10. AuthInterceptor (processes response)
// 11. UserAgentInterceptor (processes response)
```

## Best Practices

### 1. Keep Interceptors Focused

Each interceptor should have a single responsibility.

```php
// Good: Single responsibility
class AuthInterceptor { /* ... */ }
class LoggingInterceptor { /* ... */ }

// Bad: Multiple responsibilities
class AuthAndLoggingInterceptor { /* ... */ }
```

### 2. Order Matters

Add interceptors in the correct order.

```php
// Correct order:
->addInterceptor(new AuthInterceptor())      // Add auth first
->addInterceptor(new LoggingInterceptor())   // Log with auth
->addInterceptor(new ErrorHandlerInterceptor()) // Handle errors last

// Wrong order:
->addInterceptor(new ErrorHandlerInterceptor()) // ❌ Errors before auth
->addInterceptor(new AuthInterceptor())
```

### 3. Don't Modify Original Request

Always create a new request when modifying.

```php
// Good: Create new request
$newRequest = $request->withHeader('X-Custom', 'value');
return $chain->proceed($newRequest);

// Bad: Modify original (may not work)
$request->headers['X-Custom'] = 'value';
return $chain->proceed($request);
```

### 4. Handle Errors Gracefully

Catch and handle exceptions appropriately.

```php
public function intercept(Chain $chain): Response
{
    try {
        $response = $chain->proceed($chain->request());
        return $response;
    } catch (\Exception $e) {
        // Log error
        error_log("Request failed: {$e->getMessage()}");

        // Re-throw or handle
        throw $e;
    }
}
```

### 5. Avoid Heavy Processing

Interceptors run on every request. Keep them fast.

```php
// Good: Fast operation
$request = $request->withHeader('X-Request-Id', uniqid());

// Bad: Slow operation
$request = $request->withHeader('X-Signature', $this->generateExpensiveSignature());
```

## Testing Interceptors

### Unit Testing

```php
use PHPUnit\Framework\TestCase;

class AuthInterceptorTest extends TestCase
{
    public function testAddsAuthHeader(): void
    {
        $interceptor = new AuthInterceptor('test-key');

        $request = new Request('GET', 'https://api.example.com/users', [], null);
        $chain = new MockChain($request);

        $response = $interceptor->intercept($chain);

        $this->assertEquals('Bearer test-key', $chain->getModifiedRequest()->getHeader('Authorization'));
    }
}
```

### Integration Testing

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://httpbin.org')
    ->client($client)
    ->addInterceptor(new AuthInterceptor('test-key'))
    ->build();

$api = $retrofit->create(TestApi::class);
$result = $api->get(); // Verify auth header was sent
```

## Limitations

- Interceptors are synchronous (no async support)
- Cannot selectively disable interceptors per request
- All requests through a Retrofit instance use the same interceptors
- Cannot modify the HTTP client from an interceptor

## See Also

- [Headers](headers.md) - Alternative ways to add headers
- [Retry Policies](retry.md) - Built-in retry mechanism
- [Caching](caching.md) - Built-in caching mechanism
- [Client Configuration](client-configuration.md) - Configure HTTP client
