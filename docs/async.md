# Async Requests

Retrofit PHP supports asynchronous requests using Guzzle Promises, allowing you to execute multiple requests concurrently for better performance.

## Requirements

- Guzzle HTTP Client (PSR-18 clients do not support async)
- `guzzlehttp/promises` library

## Basic Async Request

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

## Parallel Requests

Execute multiple requests simultaneously to improve performance.

### Basic Parallel Execution

```php
use GuzzleHttp\Promise\Utils;

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
echo $responses['comment']->body['text'];
```

### Batch Processing

Process multiple items concurrently.

```php
// Fetch 10 posts in parallel
$promises = [];
for ($i = 1; $i <= 10; $i++) {
    $call = $proxy->invoke('getPost', [$i]);
    $promises[] = $call->executeAsync();
}

// Wait for all requests to complete
$responses = Utils::unwrap($promises);

foreach ($responses as $response) {
    echo $response->body['title'] . "\n";
}
```

## Promise Callbacks

Handle responses and errors with promise callbacks.

### Success and Error Handlers

```php
$call = $proxy->invoke('getUser', [1]);

$promise = $call->executeAsync()
    ->then(
        function ($response) {
            // Success handler
            echo "Got user: {$response->body['name']}\n";
            return $response;
        },
        function ($exception) {
            // Error handler
            echo "Error: {$exception->getMessage()}\n";
            throw $exception;
        }
    );

$promise->wait();
```

### Using otherwise() for Errors

```php
$call = $proxy->invoke('getUser', [1]);

$promise = $call->executeAsync()
    ->then(function ($response) {
        echo "Got user: {$response->body['name']}\n";
        return $response;
    })
    ->otherwise(function ($exception) {
        echo "Error: {$exception->getMessage()}\n";
        return null;
    });

$result = $promise->wait();
```

### Chaining Promises

```php
$call = $proxy->invoke('getUser', [1]);

$promise = $call->executeAsync()
    ->then(function ($response) use ($proxy) {
        $userId = $response->body['id'];

        // Chain another async request
        $postsCall = $proxy->invoke('getUserPosts', [$userId]);
        return $postsCall->executeAsync();
    })
    ->then(function ($response) {
        echo "User has " . count($response->body) . " posts\n";
    });

$promise->wait();
```

## Error Handling

### Catching Exceptions

```php
use GuzzleHttp\Promise\Utils;

$promises = [
    'user' => $userCall->executeAsync(),
    'post' => $postCall->executeAsync(),
];

try {
    $responses = Utils::unwrap($promises);
} catch (\Exception $e) {
    echo "One or more requests failed: {$e->getMessage()}\n";
}
```

### Settling Promises

Get results even if some promises fail.

```php
use GuzzleHttp\Promise\Utils;

$promises = [
    'user' => $userCall->executeAsync(),
    'post' => $postCall->executeAsync(),
    'invalid' => $invalidCall->executeAsync(),
];

// Settle all promises (don't throw on failure)
$results = Utils::settle($promises)->wait();

foreach ($results as $key => $result) {
    if ($result['state'] === 'fulfilled') {
        echo "$key succeeded: {$result['value']->body['name']}\n";
    } else {
        echo "$key failed: {$result['reason']->getMessage()}\n";
    }
}
```

### Inspection

Check promise state without waiting.

```php
$promise = $call->executeAsync();

// Do some work...

if ($promise->getState() === 'pending') {
    echo "Still loading...\n";
} elseif ($promise->getState() === 'fulfilled') {
    echo "Done!\n";
} elseif ($promise->getState() === 'rejected') {
    echo "Failed!\n";
}
```

## Advanced Patterns

### Concurrent Limit

Limit the number of concurrent requests.

```php
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\EachPromise;

$requests = range(1, 100); // 100 requests

$promises = function () use ($requests, $proxy) {
    foreach ($requests as $id) {
        $call = $proxy->invoke('getPost', [$id]);
        yield $call->executeAsync();
    }
};

// Process with max 10 concurrent requests
$eachPromise = new EachPromise($promises(), [
    'concurrency' => 10,
    'fulfilled' => function ($response) {
        echo "Loaded: {$response->body['title']}\n";
    },
    'rejected' => function ($reason) {
        echo "Failed: {$reason->getMessage()}\n";
    },
]);

$eachPromise->promise()->wait();
```

### Racing Promises

Use the first promise to resolve.

```php
use GuzzleHttp\Promise\Utils;

// Try multiple endpoints, use whichever responds first
$promises = [
    $proxy->invoke('getUserFromCache', [1])->executeAsync(),
    $proxy->invoke('getUserFromDB', [1])->executeAsync(),
    $proxy->invoke('getUserFromAPI', [1])->executeAsync(),
];

$fastest = Utils::any($promises)->wait();
echo "Got user from fastest source: {$fastest->body['name']}\n";
```

### Mapping Over Results

```php
use GuzzleHttp\Promise\Utils;

$userIds = [1, 2, 3, 4, 5];

$promises = array_map(function ($id) use ($proxy) {
    $call = $proxy->invoke('getUser', [$id]);
    return $call->executeAsync();
}, $userIds);

$responses = Utils::unwrap($promises);

$names = array_map(function ($response) {
    return $response->body['name'];
}, $responses);

print_r($names);
```

## Performance Considerations

### Connection Pooling

Guzzle automatically reuses connections for better performance.

```php
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;

// Configure connection pool
$client = GuzzleHttpClient::create([
    'curl' => [
        CURLOPT_MAXCONNECTS => 20,  // Max connections in pool
    ],
]);
```

### Timeouts

Set reasonable timeouts for async requests.

```php
$client = GuzzleHttpClient::create([
    'timeout' => 30,           // Total timeout
    'connect_timeout' => 5,    // Connection timeout
]);
```

### Memory Management

For large batches, process in chunks to avoid memory issues.

```php
$allIds = range(1, 1000);
$chunks = array_chunk($allIds, 50);

foreach ($chunks as $chunk) {
    $promises = [];
    foreach ($chunk as $id) {
        $call = $proxy->invoke('getUser', [$id]);
        $promises[] = $call->executeAsync();
    }

    $responses = Utils::unwrap($promises);

    // Process responses
    foreach ($responses as $response) {
        processUser($response->body);
    }

    // Free memory
    unset($promises, $responses);
}
```

## Complete Example

```php
use GuzzleHttp\Promise\Utils;

interface UserApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/users/{id}/posts')]
    public function getUserPosts(#[Path('id')] int $id): array;

    #[GET('/posts/{id}/comments')]
    public function getPostComments(#[Path('id')] int $id): array;
}

// Get proxy
$reflection = new ReflectionClass($retrofit);
$method = $reflection->getMethod('getServiceProxy');
$method->setAccessible(true);
$proxy = $method->invoke($retrofit, UserApi::class);

// Fetch user data in parallel
$userCall = $proxy->invoke('getUser', [1]);
$postsCall = $proxy->invoke('getUserPosts', [1]);

$promises = [
    'user' => $userCall->executeAsync(),
    'posts' => $postsCall->executeAsync(),
];

try {
    $responses = Utils::unwrap($promises);

    $user = $responses['user']->body;
    $posts = $responses['posts']->body;

    echo "User: {$user['name']}\n";
    echo "Posts: " . count($posts) . "\n";

    // Fetch comments for all posts in parallel
    $commentPromises = [];
    foreach ($posts as $post) {
        $call = $proxy->invoke('getPostComments', [$post['id']]);
        $commentPromises[$post['id']] = $call->executeAsync();
    }

    $commentResponses = Utils::unwrap($commentPromises);

    foreach ($commentResponses as $postId => $response) {
        echo "Post {$postId} has " . count($response->body) . " comments\n";
    }

} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

## Limitations

- Only available with Guzzle HTTP Client
- PSR-18 clients do not support async operations
- Requires understanding of promises and async patterns
- Debugging async code can be more complex

## See Also

- [Client Configuration](client-configuration.md) - Configure Guzzle for async
- [Retry Policies](retry.md) - Automatic retries work with async
- [Examples](examples.md) - More async examples
