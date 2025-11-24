# Headers

Retrofit PHP provides multiple ways to add HTTP headers to your requests.

## Static Headers

Define headers that are the same for every request using the `#[Headers]` attribute.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Headers;

interface ApiService
{
    #[GET('/users')]
    #[Headers(
        'Accept: application/json',
        'X-Api-Version: v1'
    )]
    public function getUsers(): array;
}
```

### Multiple Headers

```php
#[GET('/data')]
#[Headers(
    'Accept: application/json',
    'Content-Type: application/json',
    'X-Client-Version: 1.0.0',
    'X-Request-Source: mobile-app'
)]
public function getData(): array;
```

### Alternative Syntax

```php
#[GET('/users')]
#[Headers([
    'Accept' => 'application/json',
    'X-Api-Version' => 'v1'
])]
public function getUsers(): array;
```

## Dynamic Headers

Pass headers as function parameters using the `#[Header]` attribute.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Header;

interface ApiService
{
    #[GET('/users')]
    public function getUsers(
        #[Header('Authorization')] string $token
    ): array;
}

// Usage
$api = $retrofit->create(ApiService::class);

$users = $api->getUsers('Bearer my-secret-token');
// Request includes: Authorization: Bearer my-secret-token
```

### Multiple Dynamic Headers

```php
#[GET('/data')]
public function getData(
    #[Header('Authorization')] string $token,
    #[Header('X-Request-Id')] string $requestId,
    #[Header('X-User-Id')] int $userId
): array;

// Usage
$data = $api->getData(
    'Bearer token123',
    'req-' . uniqid(),
    42
);
```

### Optional Headers

```php
#[GET('/users')]
public function getUsers(
    #[Header('Authorization')] ?string $token = null,
    #[Header('X-Debug')] ?string $debug = null
): array;

// If null, the header won't be sent
$users = $api->getUsers();                    // No extra headers
$users = $api->getUsers('Bearer token123');   // Authorization header only
$users = $api->getUsers(null, 'true');        // X-Debug header only
```

## Header Map

Pass multiple headers as an associative array using `#[HeaderMap]`.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Parameter\HeaderMap;

interface ApiService
{
    #[GET('/users')]
    public function getUsers(#[HeaderMap] array $headers): array;
}

// Usage
$api = $retrofit->create(ApiService::class);

$users = $api->getUsers([
    'Authorization' => 'Bearer my-token',
    'X-Request-Id' => 'req-123',
    'X-User-Agent' => 'MyApp/1.0'
]);
```

### Combining Header Parameters

```php
#[GET('/users')]
public function getUsers(
    #[Header('Authorization')] string $token,
    #[HeaderMap] array $additionalHeaders = []
): array;

// Usage
$users = $api->getUsers('Bearer token123', [
    'X-Request-Id' => 'req-123',
    'X-Custom-Header' => 'value'
]);
```

## Combining Static and Dynamic Headers

You can mix static and dynamic headers together.

```php
interface ApiService
{
    #[GET('/users')]
    #[Headers(
        'Accept: application/json',
        'X-Api-Version: v1'
    )]
    public function getUsers(
        #[Header('Authorization')] string $token,
        #[Header('X-Request-Id')] ?string $requestId = null
    ): array;
}

// All three types of headers will be sent:
// - Accept: application/json (static)
// - X-Api-Version: v1 (static)
// - Authorization: Bearer token123 (dynamic)
// - X-Request-Id: req-xyz (dynamic, if provided)
$users = $api->getUsers('Bearer token123', 'req-xyz');
```

## Client-Level Headers

Set default headers for all requests at the client level.

```php
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;

$client = GuzzleHttpClient::create([
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
        'X-Client-Id' => 'client-12345'
    ]
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->build();

// All requests will include the client-level headers
```

## Header Priority

When the same header is defined at multiple levels, the priority is:

1. **Method-level dynamic headers** (`#[Header]`, `#[HeaderMap]`) - Highest priority
2. **Method-level static headers** (`#[Headers]`)
3. **Client-level headers** - Lowest priority

```php
// Client configuration
$client = GuzzleHttpClient::create([
    'headers' => ['X-Version' => '1.0']  // Priority 3
]);

interface ApiService
{
    #[GET('/users')]
    #[Headers('X-Version: 2.0')]  // Priority 2
    public function getUsers(
        #[Header('X-Version')] string $version  // Priority 1 (wins)
    ): array;
}

// The request will have: X-Version: 3.0
$users = $api->getUsers('3.0');
```

## Common Use Cases

### Authentication

```php
interface ApiService
{
    #[GET('/users')]
    public function getUsers(
        #[Header('Authorization')] string $bearerToken
    ): array;

    #[GET('/admin/users')]
    public function getAdminUsers(
        #[Header('X-Api-Key')] string $apiKey
    ): array;
}

// Usage
$users = $api->getUsers('Bearer ' . $accessToken);
$adminUsers = $api->getAdminUsers($apiKey);
```

### Content Negotiation

```php
#[GET('/data')]
#[Headers('Accept: application/json')]
public function getJson(): array;

#[GET('/data')]
#[Headers('Accept: application/xml')]
public function getXml(): string;

#[GET('/data')]
public function getData(
    #[Header('Accept')] string $contentType
): mixed;
```

### Request Tracking

```php
#[GET('/users')]
public function getUsers(
    #[Header('X-Request-Id')] string $requestId,
    #[Header('X-Correlation-Id')] ?string $correlationId = null
): array;

// Usage with unique IDs
$requestId = uniqid('req-');
$correlationId = $_SERVER['HTTP_X_CORRELATION_ID'] ?? null;
$users = $api->getUsers($requestId, $correlationId);
```

### Custom Metadata

```php
#[POST('/events')]
public function logEvent(
    #[Body] array $event,
    #[HeaderMap] array $metadata = []
): array;

// Usage
$api->logEvent(
    ['type' => 'user_login', 'timestamp' => time()],
    [
        'X-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
        'X-IP-Address' => $_SERVER['REMOTE_ADDR'],
        'X-Session-Id' => session_id()
    ]
);
```

## See Also

- [HTTP Methods](http-methods.md) - Learn about different HTTP methods
- [Interceptors](interceptors.md) - Add headers programmatically with interceptors
- [Client Configuration](client-configuration.md) - Configure default headers
