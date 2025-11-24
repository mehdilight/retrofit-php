<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use GuzzleHttp\Promise\Utils;

/**
 * API interface - same as sync version
 */
interface JsonPlaceholderApi
{
    #[GET('/posts/{id}')]
    public function getPost(#[Path('id')] int $id): array;

    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/comments/{id}')]
    public function getComment(#[Path('id')] int $id): array;
}

$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(JsonPlaceholderApi::class);

echo "=== Async Example ===\n\n";

// ============================================
// Example 1: Sequential requests (slow)
// ============================================
echo "1. Sequential requests (one by one):\n";
$start = microtime(true);

$post = $api->getPost(1);
$user = $api->getUser(1);
$comment = $api->getComment(1);

$sequentialTime = round((microtime(true) - $start) * 1000);
echo "   Post: {$post['title']}\n";
echo "   User: {$user['name']}\n";
echo "   Comment by: {$comment['email']}\n";
echo "   Time: {$sequentialTime}ms\n\n";

// ============================================
// Example 2: Parallel requests using Call (fast)
// ============================================
echo "2. Parallel requests (using Call->executeAsync):\n";
$start = microtime(true);

// Get the ServiceProxy to invoke methods and get Call objects
$reflection = new ReflectionClass($retrofit);
$method = $reflection->getMethod('getServiceProxy');
$method->setAccessible(true);
$proxy = $method->invoke($retrofit, JsonPlaceholderApi::class);

// Create calls
$postCall = $proxy->invoke('getPost', [2]);
$userCall = $proxy->invoke('getUser', [2]);
$commentCall = $proxy->invoke('getComment', [2]);

// Execute all in parallel
$promises = [
    'post' => $postCall->executeAsync(),
    'user' => $userCall->executeAsync(),
    'comment' => $commentCall->executeAsync(),
];

$responses = Utils::unwrap($promises);

$parallelTime = round((microtime(true) - $start) * 1000);
echo "   Post: {$responses['post']->body['title']}\n";
echo "   User: {$responses['user']->body['name']}\n";
echo "   Comment by: {$responses['comment']->body['email']}\n";
echo "   Time: {$parallelTime}ms\n\n";

// ============================================
// Example 3: Async with callbacks
// ============================================
echo "3. Async with callbacks:\n";

$postCall = $proxy->invoke('getPost', [3]);
$promise = $postCall->executeAsync()
    ->then(function ($response) {
        echo "   ✓ Received post: {$response->body['title']}\n";
        return $response;
    })
    ->then(function ($response) {
        echo "   ✓ Post ID: {$response->body['id']}\n";
        return $response;
    });

$promise->wait();

echo "\n";

// ============================================
// Example 4: Batch requests
// ============================================
echo "4. Batch fetch (10 posts in parallel):\n";
$start = microtime(true);

$promises = [];
for ($i = 1; $i <= 10; $i++) {
    $call = $proxy->invoke('getPost', [$i]);
    $promises[] = $call->executeAsync();
}

$responses = Utils::unwrap($promises);
$batchTime = round((microtime(true) - $start) * 1000);

foreach ($responses as $i => $response) {
    $id = $response->body['id'];
    $title = substr($response->body['title'], 0, 30) . '...';
    echo "   [{$id}] {$title}\n";
}
echo "   Time for 10 requests: {$batchTime}ms\n\n";

// ============================================
// Summary
// ============================================
echo "=== Performance Summary ===\n";
echo "Sequential (3 requests): {$sequentialTime}ms\n";
echo "Parallel (3 requests):   {$parallelTime}ms\n";
$speedup = round($sequentialTime / max($parallelTime, 1), 1);
echo "Speedup: ~{$speedup}x faster\n";
