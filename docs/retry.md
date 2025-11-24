# Retry Policies

Automatically retry failed requests with configurable backoff strategies to handle transient failures and rate limiting.

## Basic Usage

### Default Retry Policy

The default policy retries on common server errors and rate limits.

```php
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy(RetryPolicy::default())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

// Automatically retries on: 429, 500, 502, 503, 504
```

### No Retries

Disable retries completely.

```php
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy(RetryPolicy::none())
    ->build();
```

## Custom Retry Policy

Build a custom retry policy with specific configuration.

### Basic Configuration

```php
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)                          // 1 original + 4 retries
    ->retryOnStatusCodes([429, 500, 503])     // Which HTTP status codes to retry
    ->retryOnExceptions([\RuntimeException::class])  // Which exceptions to retry
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 1000,     // Start with 1 second
        multiplier: 2.0,        // Double each time
        maxDelayMs: 30000,      // Cap at 30 seconds
        jitter: true            // Add randomization
    ))
    ->build();

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->retryPolicy($retryPolicy)
    ->build();
```

### Configuration Options

```php
$retryPolicy = RetryPolicy::builder()
    // Maximum number of attempts (includes original request)
    ->maxAttempts(3)

    // HTTP status codes that trigger retries
    ->retryOnStatusCodes([
        429,  // Too Many Requests
        500,  // Internal Server Error
        502,  // Bad Gateway
        503,  // Service Unavailable
        504,  // Gateway Timeout
    ])

    // Exception types that trigger retries
    ->retryOnExceptions([
        \RuntimeException::class,
        \GuzzleHttp\Exception\ConnectException::class,
    ])

    // Backoff strategy (see below)
    ->backoffStrategy(new ExponentialBackoff())

    ->build();
```

## Backoff Strategies

Control the delay between retry attempts.

### Exponential Backoff

Recommended for most use cases. Increases delay exponentially.

```php
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;

$backoff = new ExponentialBackoff(
    baseDelayMs: 1000,      // Start with 1 second
    multiplier: 2.0,        // Double each time
    maxDelayMs: 60000,      // Cap at 60 seconds (optional)
    jitter: true            // Add randomization (optional)
);

// Retry delays:
// Attempt 1: 1000ms  (1s)
// Attempt 2: 2000ms  (2s)
// Attempt 3: 4000ms  (4s)
// Attempt 4: 8000ms  (8s)
// Attempt 5: 16000ms (16s)
```

#### With Jitter

Jitter adds randomization to prevent thundering herd problems.

```php
$backoff = new ExponentialBackoff(
    baseDelayMs: 1000,
    multiplier: 2.0,
    maxDelayMs: 60000,
    jitter: true  // Randomize delays
);

// Example delays with jitter:
// Attempt 1: 876ms   (random between 0-1000ms)
// Attempt 2: 1432ms  (random between 0-2000ms)
// Attempt 3: 3891ms  (random between 0-4000ms)
```

### Fixed Backoff

Wait a fixed duration between retries.

```php
use Phpmystic\RetrofitPhp\Retry\FixedBackoff;

$backoff = new FixedBackoff(delayMs: 5000);

// All retry delays: 5000ms (5 seconds)
```

### Linear Backoff

Increases delay linearly.

```php
use Phpmystic\RetrofitPhp\Retry\LinearBackoff;

$backoff = new LinearBackoff(
    initialDelayMs: 1000,   // Start at 1 second
    incrementMs: 1000,      // Increase by 1 second each time
    maxDelayMs: 10000       // Cap at 10 seconds (optional)
);

// Retry delays:
// Attempt 1: 1000ms  (1s)
// Attempt 2: 2000ms  (2s)
// Attempt 3: 3000ms  (3s)
// Attempt 4: 4000ms  (4s)
// Attempt 5: 5000ms  (5s)
```

### Custom Backoff

Implement your own backoff strategy.

```php
use Phpmystic\RetrofitPhp\Contracts\BackoffStrategy;

class CustomBackoff implements BackoffStrategy
{
    public function calculateDelay(int $attempt): int
    {
        // Custom logic to calculate delay in milliseconds
        // $attempt is 1-indexed (first retry is attempt 1)
        return $attempt * 1000 * $attempt; // Quadratic backoff
    }
}

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)
    ->backoffStrategy(new CustomBackoff())
    ->build();
```

## Advanced Configuration

### Retry on Specific Status Codes

```php
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(3)
    ->retryOnStatusCodes([
        429,  // Rate limited
        503,  // Service unavailable
    ])
    ->backoffStrategy(new ExponentialBackoff(1000))
    ->build();
```

### Retry on Network Errors

```php
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)
    ->retryOnExceptions([
        ConnectException::class,    // Connection failures
        RequestException::class,    // Request errors
    ])
    ->backoffStrategy(new ExponentialBackoff(1000))
    ->build();
```

### Combining Conditions

```php
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)
    ->retryOnStatusCodes([429, 500, 502, 503, 504])
    ->retryOnExceptions([
        \RuntimeException::class,
        \GuzzleHttp\Exception\ConnectException::class,
    ])
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 1000,
        multiplier: 2.0,
        maxDelayMs: 30000,
        jitter: true
    ))
    ->build();
```

## Use Cases

### Rate Limiting

Handle API rate limits with exponential backoff.

```php
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(5)
    ->retryOnStatusCodes([429])  // Too Many Requests
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 2000,      // Start with 2 seconds
        multiplier: 2.0,
        maxDelayMs: 120000,     // Max 2 minutes
        jitter: true
    ))
    ->build();
```

### Server Errors

Retry transient server errors.

```php
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(3)
    ->retryOnStatusCodes([500, 502, 503, 504])
    ->backoffStrategy(new ExponentialBackoff(1000))
    ->build();
```

### Network Failures

Retry on connection issues.

```php
use GuzzleHttp\Exception\ConnectException;

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(4)
    ->retryOnExceptions([ConnectException::class])
    ->backoffStrategy(new LinearBackoff(
        initialDelayMs: 1000,
        incrementMs: 500
    ))
    ->build();
```

### Critical Operations

Aggressive retries for important operations.

```php
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(10)  // Try up to 10 times
    ->retryOnStatusCodes([429, 500, 502, 503, 504])
    ->retryOnExceptions([
        \RuntimeException::class,
        \GuzzleHttp\Exception\ConnectException::class,
    ])
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 500,
        multiplier: 1.5,
        maxDelayMs: 60000,
        jitter: true
    ))
    ->build();
```

## Best Practices

### 1. Use Exponential Backoff with Jitter

Prevents thundering herd and respects server recovery time.

```php
$backoff = new ExponentialBackoff(
    baseDelayMs: 1000,
    multiplier: 2.0,
    maxDelayMs: 60000,
    jitter: true  // Important!
);
```

### 2. Set Reasonable Max Attempts

Too many retries can delay error reporting.

```php
// Good: 3-5 attempts for most APIs
->maxAttempts(5)

// Avoid: Too many attempts
->maxAttempts(50)  // This could take a very long time
```

### 3. Cap Maximum Delay

Prevent extremely long waits.

```php
new ExponentialBackoff(
    baseDelayMs: 1000,
    multiplier: 2.0,
    maxDelayMs: 60000  // Cap at 1 minute
);
```

### 4. Match Status Codes to API

Different APIs use different status codes.

```php
// Standard REST API
->retryOnStatusCodes([429, 500, 502, 503, 504])

// Custom API that uses 509 for rate limits
->retryOnStatusCodes([429, 500, 502, 503, 504, 509])
```

### 5. Consider Idempotency

Only retry idempotent operations (GET, PUT, DELETE) automatically. Be careful with POST.

```php
// Safe to retry
#[GET('/users/{id}')]
#[PUT('/users/{id}')]
#[DELETE('/users/{id}')]

// May not be safe to retry (could create duplicates)
#[POST('/users')]  // Use with caution
```

## Monitoring Retries

Log retry attempts for monitoring and debugging.

```php
// Use an interceptor to log retries
class RetryLoggingInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $attempt = 1;
        $startTime = microtime(true);

        while (true) {
            try {
                $response = $chain->proceed($chain->request());

                if ($attempt > 1) {
                    $duration = microtime(true) - $startTime;
                    error_log("Request succeeded on attempt $attempt after {$duration}s");
                }

                return $response;
            } catch (\Exception $e) {
                $attempt++;
                error_log("Attempt $attempt failed: {$e->getMessage()}");
                throw $e;
            }
        }
    }
}
```

## Limitations

- Retries happen synchronously (blocks execution)
- Cannot customize retry logic per endpoint
- All requests in a Retrofit instance share the same retry policy

## See Also

- [Interceptors](interceptors.md) - Implement custom retry logic
- [Client Configuration](client-configuration.md) - Configure timeouts
- [Async Requests](async.md) - Retries work with async requests
