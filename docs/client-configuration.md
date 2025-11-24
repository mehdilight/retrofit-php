# HTTP Client Configuration

Retrofit PHP supports multiple HTTP client implementations and offers flexible configuration options.

## Guzzle HTTP Client

The default HTTP client implementation uses Guzzle, providing powerful configuration options.

### Basic Configuration

```php
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Retrofit;

$client = GuzzleHttpClient::create([
    'timeout' => 30,           // Request timeout in seconds
    'connect_timeout' => 10,   // Connection timeout in seconds
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->build();
```

### Common Options

```php
$client = GuzzleHttpClient::create([
    // Timeouts
    'timeout' => 30,                // Total request timeout
    'connect_timeout' => 10,        // Connection timeout
    'read_timeout' => 25,           // Time to read response

    // Headers
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
    ],

    // SSL/TLS
    'verify' => true,               // Verify SSL certificates (default: true)
    // 'verify' => '/path/to/ca-bundle.crt',  // Custom CA bundle
    'cert' => '/path/to/client.pem',  // Client certificate

    // Proxy
    'proxy' => 'tcp://localhost:8125',
    // 'proxy' => [
    //     'http'  => 'tcp://localhost:8125',
    //     'https' => 'tcp://localhost:9124',
    // ],

    // Debugging
    'debug' => false,               // Enable debug output

    // HTTP version
    'version' => '1.1',             // HTTP protocol version

    // Redirects
    'allow_redirects' => true,      // Follow redirects
    // 'allow_redirects' => [
    //     'max' => 5,
    //     'strict' => true,
    //     'referer' => true,
    // ],
]);
```

### SSL Configuration

```php
// Production - verify SSL certificates
$client = GuzzleHttpClient::create([
    'verify' => true,
]);

// Development - disable SSL verification (not recommended for production)
$client = GuzzleHttpClient::create([
    'verify' => false,
]);

// Custom CA bundle
$client = GuzzleHttpClient::create([
    'verify' => '/path/to/ca-bundle.crt',
]);

// Client certificates
$client = GuzzleHttpClient::create([
    'cert' => '/path/to/client.pem',
    // or with password
    'cert' => ['/path/to/client.pem', 'password'],
]);
```

### Proxy Configuration

```php
// Simple proxy
$client = GuzzleHttpClient::create([
    'proxy' => 'tcp://localhost:8125',
]);

// Protocol-specific proxies
$client = GuzzleHttpClient::create([
    'proxy' => [
        'http'  => 'tcp://localhost:8125',
        'https' => 'tcp://localhost:9124',
        'no'    => ['.example.com', '.internal.com'],
    ],
]);

// Authenticated proxy
$client = GuzzleHttpClient::create([
    'proxy' => 'tcp://username:password@localhost:8125',
]);
```

### Authentication

```php
// Basic authentication
$client = GuzzleHttpClient::create([
    'auth' => ['username', 'password'],
]);

// Digest authentication
$client = GuzzleHttpClient::create([
    'auth' => ['username', 'password', 'digest'],
]);

// Bearer token (via headers)
$client = GuzzleHttpClient::create([
    'headers' => [
        'Authorization' => 'Bearer your-token-here',
    ],
]);
```

## PSR-18 HTTP Client

Use any PSR-18 compliant HTTP client for vendor-agnostic implementation.

### With Symfony HTTP Client

```php
use Phpmystic\RetrofitPhp\Http\Psr18HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

// Create Symfony PSR-18 client
$psr18Client = new Psr18Client();

// Wrap it with Retrofit's adapter
$httpClient = Psr18HttpClient::create($psr18Client);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($httpClient)
    ->build();
```

### With Custom PSR-17 Factories

```php
use Phpmystic\RetrofitPhp\Http\Psr18HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr18Client = new Psr18Client();
$psr17Factory = new Psr17Factory();

$httpClient = new Psr18HttpClient(
    $psr18Client,
    $psr17Factory,  // Request factory
    $psr17Factory   // Stream factory
);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($httpClient)
    ->build();
```

### Supported PSR-18 Clients

- **Symfony HTTP Client**: `symfony/http-client`
- **Guzzle PSR-18 Adapter**: `guzzlehttp/guzzle` with PSR-18 adapter
- **HTTPlug**: Any HTTPlug client
- **Custom**: Any PSR-18 compliant client

### Benefits of PSR-18

- Vendor-agnostic HTTP abstraction
- Easy to swap implementations
- Better testability with mocks
- Standards-compliant architecture

### Limitations

- No async support (use Guzzle for async operations)
- Client-specific features may not be available

## Custom HTTP Client

Implement your own HTTP client by implementing the `HttpClient` interface.

### Basic Implementation

```php
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

class CustomHttpClient implements HttpClient
{
    public function execute(Request $request): Response
    {
        // Your custom implementation
        $curlHandle = curl_init();

        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $request->url,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($request->headers),
            CURLOPT_POSTFIELDS => $request->body,
        ]);

        $responseBody = curl_exec($curlHandle);
        $statusCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $responseHeaders = curl_getinfo($curlHandle, CURLINFO_HEADER_OUT);

        curl_close($curlHandle);

        return new Response(
            code: $statusCode,
            headers: $this->parseHeaders($responseHeaders),
            body: $responseBody
        );
    }

    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = "$name: $value";
        }
        return $formatted;
    }

    private function parseHeaders(string $headerString): array
    {
        // Parse headers from string
        return [];
    }
}

// Usage
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client(new CustomHttpClient())
    ->build();
```

### With Async Support

```php
use Phpmystic\RetrofitPhp\Contracts\AsyncHttpClient;
use GuzzleHttp\Promise\PromiseInterface;

class CustomAsyncHttpClient implements AsyncHttpClient
{
    public function execute(Request $request): Response
    {
        // Synchronous execution
    }

    public function executeAsync(Request $request): PromiseInterface
    {
        // Asynchronous execution
        return new Promise(function () use ($request) {
            // Async implementation
        });
    }
}
```

## Choosing a Client

### Use Guzzle When:
- You need async/promise-based operations
- You want maximum configurability
- You need advanced features (retries, middleware, etc.)
- You're building a new application

### Use PSR-18 When:
- You want vendor-agnostic code
- You need to swap HTTP clients easily
- You're integrating into existing PSR-compliant code
- You don't need async operations

### Use Custom Client When:
- You have specific requirements
- You're integrating with legacy systems
- You need complete control over HTTP implementation
- You're optimizing for specific use cases

## Complete Configuration Example

```php
use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;

// Create fully configured Guzzle client
$client = GuzzleHttpClient::create([
    // Timeouts
    'timeout' => 30,
    'connect_timeout' => 10,

    // Default headers
    'headers' => [
        'User-Agent' => 'MyApp/2.0',
        'Accept' => 'application/json',
        'X-Client-Version' => '2.0.0',
    ],

    // SSL
    'verify' => true,

    // Redirects
    'allow_redirects' => [
        'max' => 3,
        'strict' => true,
    ],

    // HTTP version
    'version' => '1.1',
]);

// Build Retrofit with configured client
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

## See Also

- [Headers](headers.md) - Set headers at multiple levels
- [Timeouts](timeouts.md) - Configure per-endpoint timeouts
- [Async Requests](async.md) - Learn about async operations with Guzzle
- [Interceptors](interceptors.md) - Modify requests and responses
