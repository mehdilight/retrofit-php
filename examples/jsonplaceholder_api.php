<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

/**
 * JSONPlaceholder API - A fake REST API for testing
 * https://jsonplaceholder.typicode.com/
 */
interface JsonPlaceholderApi
{
    // Posts
    #[GET('/posts')]
    public function getPosts(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts/{id}')]
    public function getPost(#[Path('id')] int $id): array;

    #[POST('/posts')]
    public function createPost(#[Body] array $post): array;

    #[PUT('/posts/{id}')]
    public function updatePost(#[Path('id')] int $id, #[Body] array $post): array;

    #[DELETE('/posts/{id}')]
    public function deletePost(#[Path('id')] int $id): array;

    // Users
    #[GET('/users')]
    public function getUsers(): array;

    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    // Comments
    #[GET('/posts/{postId}/comments')]
    public function getPostComments(#[Path('postId')] int $postId): array;
}

// Create Retrofit instance
$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create(['timeout' => 30]))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

// Create API service
/** @var JsonPlaceholderApi $api */
$api = $retrofit->create(JsonPlaceholderApi::class);
echo "=== JSONPlaceholder API Example ===\n\n";

// GET - List posts
echo "1. GET /posts (first 3)...\n";
$posts = $api->getPosts(3);
foreach ($posts as $post) {
    echo "   [{$post['id']}] {$post['title']}\n";
}
echo "\n";

// GET - Single post
echo "2. GET /posts/1...\n";
$post = $api->getPost(1);
echo "   Title: {$post['title']}\n";
echo "   Body: " . substr($post['body'], 0, 50) . "...\n\n";

// POST - Create new post
echo "3. POST /posts (create new)...\n";
$newPost = $api->createPost([
    'title' => 'My New Post',
    'body' => 'This is the content of my new post.',
    'userId' => 1,
]);
echo "   Created post with ID: {$newPost['id']}\n";
echo "   Title: {$newPost['title']}\n\n";

// PUT - Update post
echo "4. PUT /posts/1 (update)...\n";
$updatedPost = $api->updatePost(1, [
    'id' => 1,
    'title' => 'Updated Title',
    'body' => 'Updated body content.',
    'userId' => 1,
]);
echo "   Updated title: {$updatedPost['title']}\n\n";

// DELETE - Delete post
echo "5. DELETE /posts/1...\n";
$api->deletePost(1);
echo "   Post deleted successfully.\n\n";

// GET - Users
echo "6. GET /users (first 3)...\n";
$users = $api->getUsers();
foreach (array_slice($users, 0, 3) as $user) {
    echo "   - {$user['name']} ({$user['email']})\n";
}
echo "\n";

// GET - Post comments
echo "7. GET /posts/1/comments...\n";
$comments = $api->getPostComments(1);
echo "   Found " . count($comments) . " comments:\n";
foreach (array_slice($comments, 0, 2) as $comment) {
    echo "   - {$comment['name']}\n";
}
