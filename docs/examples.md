# Complete Examples

Full working examples demonstrating all features of Retrofit PHP.

## Basic REST API Example

Complete example with JSONPlaceholder API.

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
use Phpmystic\RetrofitPhp\Attributes\Http\{GET, POST, PUT, DELETE};
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Query, Body};
use Phpmystic\RetrofitPhp\Attributes\Cacheable;
use Phpmystic\RetrofitPhp\Attributes\Timeout;

interface PostsApi
{
    #[GET('/posts')]
    #[Cacheable(ttl: 300)]
    public function list(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts/{id}')]
    #[Cacheable(ttl: 60)]
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

## GitHub API Example

Working with GitHub's REST API with authentication.

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\{GET, POST};
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Query, Body, Header};
use Phpmystic\RetrofitPhp\Attributes\Headers;

interface GitHubApi
{
    #[GET('/users/{username}')]
    public function getUser(
        #[Path('username')] string $username,
        #[Header('Authorization')] ?string $token = null
    ): array;

    #[GET('/users/{username}/repos')]
    public function listRepos(
        #[Path('username')] string $username,
        #[Query('type')] string $type = 'owner',
        #[Query('sort')] string $sort = 'updated',
        #[Query('per_page')] int $perPage = 10
    ): array;

    #[POST('/repos/{owner}/{repo}/issues')]
    #[Headers('Accept: application/vnd.github.v3+json')]
    public function createIssue(
        #[Path('owner')] string $owner,
        #[Path('repo')] string $repo,
        #[Body] array $issue,
        #[Header('Authorization')] string $token
    ): array;
}

$client = GuzzleHttpClient::create([
    'timeout' => 30,
    'headers' => [
        'User-Agent' => 'Retrofit-PHP-Example/1.0',
    ],
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.github.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(GitHubApi::class);

// Get user info (public, no auth required)
$user = $api->getUser('octocat');
echo "Name: {$user['name']}\n";
echo "Bio: {$user['bio']}\n";
echo "Public Repos: {$user['public_repos']}\n";

// List repositories
$repos = $api->listRepos('octocat', perPage: 5, sort: 'stars');
echo "\nTop 5 Repositories:\n";
foreach ($repos as $repo) {
    echo "- {$repo['name']} ({$repo['stargazers_count']} stars)\n";
}

// Create issue (requires authentication)
$token = 'Bearer ' . getenv('GITHUB_TOKEN');
$issue = $api->createIssue('owner', 'repo', [
    'title' => 'Bug report',
    'body' => 'Something is broken',
    'labels' => ['bug']
], $token);
echo "\nCreated issue #{$issue['number']}\n";
```

## File Upload Example

Complete file upload implementation with progress tracking.

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\FileHandling\{FileUpload, ProgressCallback};
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;

interface FileApi
{
    #[POST('/upload')]
    #[Multipart]
    public function uploadFile(
        #[Part('file')] FileUpload $file,
        #[Part('description')] string $description,
        #[Part('category')] string $category
    ): array;

    #[POST('/upload-multiple')]
    #[Multipart]
    public function uploadMultiple(
        #[Part('files')] array $files,
        #[Part('folder')] string $folder
    ): array;
}

// Progress tracking
$progress = new ProgressCallback(function ($transferred, $total) {
    if ($total > 0) {
        $percentage = ($transferred / $total) * 100;
        echo sprintf(
            "\rProgress: %.1f%% (%s / %s)",
            $percentage,
            formatBytes($transferred),
            formatBytes($total)
        );
    }
});

$client = GuzzleHttpClient::create([
    'timeout' => 600,  // 10 minutes for large files
    'progress' => $progress->getCallback(),
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(FileApi::class);

// Upload single file
echo "Uploading document...\n";
$file = FileUpload::fromPath('/path/to/document.pdf');
$result = $api->uploadFile($file, 'Important document', 'documents');
echo "\nUploaded: {$result['id']}\n";

// Upload multiple files
echo "\nUploading multiple images...\n";
$files = [
    FileUpload::fromPath('/path/to/image1.jpg'),
    FileUpload::fromPath('/path/to/image2.jpg'),
    FileUpload::fromPath('/path/to/image3.jpg'),
];
$result = $api->uploadMultiple($files, 'photos/2024');
echo "\nUploaded {$result['count']} files\n";

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
```

## Authentication & Interceptor Example

Using interceptors for authentication and logging.

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\{Interceptor, Chain};
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Attributes\Http\{GET, POST};
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Body};

// Authentication interceptor
class BearerAuthInterceptor implements Interceptor
{
    public function __construct(private string $token) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $newRequest = $request->withHeader('Authorization', "Bearer {$this->token}");
        return $chain->proceed($newRequest);
    }
}

// Logging interceptor
class LoggingInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();
        $start = microtime(true);

        echo "→ {$request->method} {$request->url}\n";

        $response = $chain->proceed($request);

        $duration = round((microtime(true) - $start) * 1000);
        echo "← {$response->code} ({$duration}ms)\n\n";

        return $response;
    }
}

// Error handler interceptor
class ErrorHandlerInterceptor implements Interceptor
{
    public function intercept(Chain $chain): Response
    {
        $response = $chain->proceed($chain->request());

        if ($response->code >= 400) {
            $message = $response->body['error'] ?? 'Unknown error';
            throw new \RuntimeException("API Error: {$message}", $response->code);
        }

        return $response;
    }
}

interface SecureApi
{
    #[GET('/user/profile')]
    public function getProfile(): array;

    #[POST('/user/posts')]
    public function createPost(#[Body] array $post): array;
}

$token = 'your-api-token-here';

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client(GuzzleHttpClient::create())
    ->addInterceptor(new BearerAuthInterceptor($token))
    ->addInterceptor(new LoggingInterceptor())
    ->addInterceptor(new ErrorHandlerInterceptor())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(SecureApi::class);

try {
    // All requests will have auth token and be logged
    $profile = $api->getProfile();
    echo "User: {$profile['name']}\n";

    $post = $api->createPost([
        'title' => 'Hello World',
        'content' => 'This is my first post'
    ]);
    echo "Created post: {$post['id']}\n";

} catch (\RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

## Async Operations Example

Parallel requests with async support.

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use GuzzleHttp\Promise\Utils;

interface DataApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/posts/{id}')]
    public function getPost(#[Path('id')] int $id): array;

    #[GET('/comments/{id}')]
    public function getComment(#[Path('id')] int $id): array;
}

$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

// Get the internal proxy for async operations
$reflection = new ReflectionClass($retrofit);
$method = $reflection->getMethod('getServiceProxy');
$method->setAccessible(true);
$proxy = $method->invoke($retrofit, DataApi::class);

// Fetch multiple resources in parallel
echo "Fetching user, post, and comments in parallel...\n";
$start = microtime(true);

$promises = [
    'user' => $proxy->invoke('getUser', [1])->executeAsync(),
    'post' => $proxy->invoke('getPost', [1])->executeAsync(),
    'comment' => $proxy->invoke('getComment', [1])->executeAsync(),
];

$responses = Utils::unwrap($promises);

$duration = round((microtime(true) - $start) * 1000);
echo "Completed in {$duration}ms\n\n";

echo "User: {$responses['user']->body['name']}\n";
echo "Post: {$responses['post']->body['title']}\n";
echo "Comment: {$responses['comment']->body['email']}\n";

// Batch processing
echo "\nFetching 10 users in parallel...\n";
$start = microtime(true);

$promises = [];
for ($i = 1; $i <= 10; $i++) {
    $call = $proxy->invoke('getUser', [$i]);
    $promises[] = $call->executeAsync();
}

$responses = Utils::unwrap($promises);
$duration = round((microtime(true) - $start) * 1000);

echo "Fetched " . count($responses) . " users in {$duration}ms\n";
foreach ($responses as $i => $response) {
    echo "- User " . ($i + 1) . ": {$response->body['name']}\n";
}
```

## Production-Ready Example

Complete production setup with all features.

```php
<?php

require 'vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Retry\{RetryPolicy, ExponentialBackoff};
use Phpmystic\RetrofitPhp\Cache\InMemoryCache;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Contracts\{Interceptor, Chain};
use Phpmystic\RetrofitPhp\Http\Response;
use Psr\Log\LoggerInterface;

// Production-ready interceptors
class ProductionAuthInterceptor implements Interceptor
{
    public function __construct(
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    public function intercept(Chain $chain): Response
    {
        $request = $chain->request();

        // Add authentication
        $newRequest = $request->withHeader('X-Api-Key', $this->apiKey);

        // Add request metadata
        $newRequest = $newRequest
            ->withHeader('X-Request-Id', uniqid('req-', true))
            ->withHeader('X-Client-Version', '1.0.0')
            ->withHeader('User-Agent', 'MyApp/1.0');

        $this->logger->info('API Request', [
            'method' => $request->method,
            'url' => $request->url,
        ]);

        $response = $chain->proceed($newRequest);

        $this->logger->info('API Response', [
            'status' => $response->code,
        ]);

        return $response;
    }
}

class ErrorHandlerInterceptor implements Interceptor
{
    public function __construct(private LoggerInterface $logger) {}

    public function intercept(Chain $chain): Response
    {
        try {
            $response = $chain->proceed($chain->request());

            if ($response->code >= 500) {
                $this->logger->error('Server Error', [
                    'status' => $response->code,
                    'body' => $response->body,
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Request Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

// API Definition
interface ProductionApi
{
    #[GET('/health')]
    public function healthCheck(): array;

    #[GET('/data')]
    public function getData(): array;
}

// Setup (use your DI container in production)
$logger = /* your PSR-3 logger */;

$client = GuzzleHttpClient::create([
    'timeout' => 30,
    'connect_timeout' => 10,
    'verify' => true,  // Always verify SSL in production
    'http_errors' => false,  // Handle errors manually
]);

$retryPolicy = RetryPolicy::builder()
    ->maxAttempts(3)
    ->retryOnStatusCodes([429, 500, 502, 503, 504])
    ->backoffStrategy(new ExponentialBackoff(
        baseDelayMs: 1000,
        multiplier: 2.0,
        maxDelayMs: 30000,
        jitter: true
    ))
    ->build();

$cache = new InMemoryCache();  // Use Redis in production
$cachePolicy = new CachePolicy(
    ttl: 300,
    onlyGetRequests: true,
    onlySuccessResponses: true
);

$retrofit = Retrofit::builder()
    ->baseUrl(getenv('API_BASE_URL'))
    ->client($client)
    ->retryPolicy($retryPolicy)
    ->cache($cache)
    ->cachePolicy($cachePolicy)
    ->addInterceptor(new ProductionAuthInterceptor(getenv('API_KEY'), $logger))
    ->addInterceptor(new ErrorHandlerInterceptor($logger))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(ProductionApi::class);

// Use the API
try {
    $health = $api->healthCheck();
    if ($health['status'] === 'ok') {
        $data = $api->getData();
        // Process data...
    }
} catch (\Exception $e) {
    // Handle gracefully
    $logger->error('Application Error', ['error' => $e->getMessage()]);
}
```

## See Also

- [HTTP Methods](http-methods.md) - HTTP method documentation
- [Parameters](parameters.md) - URL parameters
- [Request Body](request-body.md) - Request body formats
- [Headers](headers.md) - Header configuration
- [Client Configuration](client-configuration.md) - HTTP client setup
- [Converters](converters.md) - Data conversion
- [Async Requests](async.md) - Async operations
- [Retry Policies](retry.md) - Retry configuration
- [Caching](caching.md) - Response caching
- [Interceptors](interceptors.md) - Request/response interception
- [File Handling](file-handling.md) - File uploads/downloads
