# Timeouts

Configure request timeouts at the client level or per endpoint to prevent hanging requests and ensure responsive applications.

## Client-Level Timeouts

Set default timeouts for all requests using Guzzle configuration.

### Basic Configuration

```php
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;

$client = GuzzleHttpClient::create([
    'timeout' => 30,           // Total request timeout in seconds
    'connect_timeout' => 10,   // Connection timeout in seconds
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->build();
```

### Timeout Types

```php
$client = GuzzleHttpClient::create([
    // Total time allowed for the entire request (including DNS, connection, data transfer)
    'timeout' => 30,

    // Maximum time to establish a connection
    'connect_timeout' => 10,

    // Maximum time waiting to read response data
    'read_timeout' => 25,
]);
```

## Per-Endpoint Timeouts

Use the `#[Timeout]` attribute to document endpoint-specific timeout requirements.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Timeout;

interface ApiService
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

### Documentation Purpose

```php
interface ReportApi
{
    // Quick lookups - short timeout
    #[GET('/reports/{id}/summary')]
    #[Timeout(10)]
    public function getReportSummary(#[Path('id')] int $id): array;

    // Full report generation - longer timeout
    #[GET('/reports/{id}/full')]
    #[Timeout(180)]
    public function getFullReport(#[Path('id')] int $id): array;

    // Batch export - very long timeout
    #[POST('/reports/export')]
    #[Timeout(600)]
    public function exportReports(#[Body] array $options): array;
}
```

## Timeout Behavior

### What Happens on Timeout

When a request times out:
- A `GuzzleHttp\Exception\ConnectException` is thrown for connection timeouts
- A `GuzzleHttp\Exception\RequestException` is thrown for request timeouts
- The request is canceled
- No response is received

```php
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

try {
    $data = $api->getSlowData();
} catch (ConnectException $e) {
    echo "Connection timeout: {$e->getMessage()}\n";
} catch (RequestException $e) {
    echo "Request timeout: {$e->getMessage()}\n";
}
```

## Use Cases

### Quick API Calls

Fast endpoints should have short timeouts.

```php
#[GET('/ping')]
#[Timeout(2)]  // Expect response within 2 seconds
public function ping(): array;

#[GET('/cache/{key}')]
#[Timeout(1)]  // Cache should be instant
public function getCached(#[Path('key')] string $key): array;
```

### Database Queries

Moderate timeouts for database operations.

```php
#[GET('/users/{id}')]
#[Timeout(10)]  // Simple query
public function getUser(#[Path('id')] int $id): array;

#[POST('/search')]
#[Timeout(30)]  // Complex search
public function search(#[Body] array $criteria): array;
```

### Report Generation

Long timeouts for heavy processing.

```php
#[POST('/reports/generate')]
#[Timeout(300)]  // 5 minutes for report generation
public function generateReport(#[Body] array $params): array;

#[GET('/exports/{id}')]
#[Timeout(600)]  // 10 minutes for large exports
public function downloadExport(#[Path('id')] string $id): array;
```

### File Operations

Very long timeouts for file uploads/downloads.

```php
#[POST('/files/upload')]
#[Timeout(900)]  // 15 minutes for large file upload
#[Multipart]
public function uploadLargeFile(#[Part('file')] FileUpload $file): array;

#[GET('/files/{id}/download')]
#[Timeout(1800)]  // 30 minutes for large file download
#[Streaming]
public function downloadLargeFile(#[Path('id')] string $id): StreamInterface;
```

## Dynamic Timeouts

Set timeouts dynamically based on runtime conditions.

### Using Interceptors

```php
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Contracts\Chain;

class DynamicTimeoutInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Adjust timeout based on endpoint
        if (str_contains($request->url, '/upload')) {
            // Longer timeout for uploads
            $request = $request->withTimeout(600);
        } elseif (str_contains($request->url, '/cache')) {
            // Shorter timeout for cache
            $request = $request->withTimeout(2);
        }

        return $chain->proceed($request);
    }
}

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addInterceptor(new DynamicTimeoutInterceptor())
    ->build();
```

### Per-Request Configuration

Create clients with different timeout configurations for different use cases.

```php
// Fast client for quick operations
$fastClient = GuzzleHttpClient::create([
    'timeout' => 5,
    'connect_timeout' => 2,
]);

// Slow client for long operations
$slowClient = GuzzleHttpClient::create([
    'timeout' => 300,
    'connect_timeout' => 10,
]);

$fastRetrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($fastClient)
    ->build();

$slowRetrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($slowClient)
    ->build();
```

## Best Practices

### 1. Set Reasonable Defaults

Start with sensible defaults that work for most endpoints.

```php
$client = GuzzleHttpClient::create([
    'timeout' => 30,           // Good default for most APIs
    'connect_timeout' => 10,   // Allow time for connection
]);
```

### 2. Override for Special Cases

Use longer timeouts only when necessary.

```php
// Most endpoints use default 30s timeout

#[POST('/reports/generate')]
#[Timeout(300)]  // Exception: report generation needs 5 minutes
public function generateReport(array $params): array;
```

### 3. Consider User Experience

Timeouts affect user experience. Too short = errors, too long = bad UX.

```php
// Web requests - keep it short for responsive UI
#[Timeout(10)]

// Background jobs - can be longer
#[Timeout(300)]

// Webhook handlers - very short
#[Timeout(5)]
```

### 4. Combine with Retry Policies

Use retries for transient failures, not as a substitute for proper timeouts.

```php
// Good: Short timeout with retries
$client = GuzzleHttpClient::create(['timeout' => 10]);
$retryPolicy = RetryPolicy::builder()->maxAttempts(3)->build();

// Bad: Very long timeout without retries
$client = GuzzleHttpClient::create(['timeout' => 300]);
```

### 5. Test with Real Conditions

Test timeouts under realistic network conditions.

```php
// Simulate slow network in tests
$client = GuzzleHttpClient::create([
    'timeout' => 5,
    'delay' => 1000,  // Add 1s delay to all requests
]);
```

### 6. Monitor Timeout Errors

Track timeout errors to identify slow endpoints.

```php
try {
    $result = $api->getSlowData();
} catch (RequestException $e) {
    if ($e->getCode() === 28) {  // cURL timeout error
        // Log timeout for monitoring
        error_log("Timeout on: " . $e->getRequest()->getUri());
    }
    throw $e;
}
```

## Timeout Hierarchy

Timeouts are applied in this order:

1. **Guzzle-level timeouts** (most specific)
2. **Client-level timeouts** (default for all requests)
3. **System timeouts** (PHP max_execution_time)

```php
// Client default: 30 seconds
$client = GuzzleHttpClient::create(['timeout' => 30]);

// This request will timeout after 30 seconds
$api->getUser(1);

// Note: #[Timeout] attribute is for documentation only
// To actually enforce different timeouts, use interceptors or separate clients
```

## Common Timeout Values

```php
// Quick cache lookups
'timeout' => 2

// Standard API calls
'timeout' => 30

// Database queries
'timeout' => 60

// Report generation
'timeout' => 300

// File uploads/downloads
'timeout' => 600-1800

// Webhook calls
'timeout' => 5

// Health checks
'timeout' => 3
```

## Troubleshooting

### Timeout Too Short

**Symptom**: Requests fail with timeout errors frequently.

**Solution**: Increase timeout or optimize the endpoint.

```php
// Before: Too short
#[Timeout(5)]

// After: More reasonable
#[Timeout(30)]
```

### Timeout Too Long

**Symptom**: Application hangs waiting for slow responses.

**Solution**: Decrease timeout and handle errors gracefully.

```php
// Before: Too long
#[Timeout(300)]

// After: More responsive
#[Timeout(30)]
```

### Inconsistent Timeouts

**Symptom**: Some requests succeed, others timeout unpredictably.

**Solution**: Check network conditions, server load, and retry policies.

```php
// Add retries for transient failures
$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(3)
    ->retryOnExceptions([ConnectException::class])
    ->build();
```

## Limitations

- `#[Timeout]` attribute is for documentation only
- Actual timeout enforcement requires Guzzle configuration
- Cannot set different timeouts per request without interceptors
- All timeouts are in seconds (Guzzle limitation)

## See Also

- [Client Configuration](client-configuration.md) - Configure client timeouts
- [Retry Policies](retry.md) - Combine timeouts with retries
- [Interceptors](interceptors.md) - Implement dynamic timeout logic
