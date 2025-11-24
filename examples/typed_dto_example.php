<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use Phpmystic\RetrofitPhp\Attributes\ArrayType;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;

// ============================================
// Define DTO Classes
// ============================================

class Geo
{
    public string $lat;
    public string $lng;
}

class Address
{
    public string $street;
    public string $suite;
    public string $city;
    public string $zipcode;
    public Geo $geo;  // Nested object
}

class Company
{
    public string $name;
    public string $catchPhrase;
    public string $bs;
}

class User
{
    public int $id;
    public string $name;
    public string $username;
    public string $email;
    public Address $address;  // Nested object
    public string $phone;
    public string $website;
    public Company $company;  // Nested object
}

class Post
{
    #[SerializedName('userId')]
    public int $authorId;  // Maps from "userId" in JSON

    public int $id;
    public string $title;
    public string $body;
}

class Comment
{
    #[SerializedName('postId')]
    public int $postId;

    public int $id;
    public string $name;
    public string $email;
    public string $body;
}

class UserWithPosts
{
    public int $id;
    public string $name;
    public string $email;

    #[ArrayType(Post::class)]
    public array $posts = [];  // Array of Post objects
}

// ============================================
// Define API Interface with Typed Returns
// ============================================

interface JsonPlaceholderApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): User;

    #[GET('/posts/{id}')]
    public function getPost(#[Path('id')] int $id): Post;

    #[GET('/posts')]
    public function getPosts(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts')]
    #[ResponseType(Post::class, isArray: true)]
    public function getPostsTyped(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts/{id}/comments')]
    #[ResponseType(Comment::class, isArray: true)]
    public function getPostComments(#[Path('id')] int $postId): array;
}

// ============================================
// Create Retrofit with TypedJsonConverterFactory
// ============================================

$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new TypedJsonConverterFactory())  // Enables DTO hydration
    ->build();

$api = $retrofit->create(JsonPlaceholderApi::class);


echo "=== Typed DTO Example ===\n\n";

// ============================================
// Example 1: Get User with Nested Objects
// ============================================
echo "1. Get User (with nested Address, Geo, Company):\n";
$user = $api->getUser(1);

echo "   Type: " . get_class($user) . "\n";
echo "   Name: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Address Type: " . get_class($user->address) . "\n";
echo "   City: {$user->address->city}\n";
echo "   Geo Type: " . get_class($user->address->geo) . "\n";
echo "   Coordinates: {$user->address->geo->lat}, {$user->address->geo->lng}\n";
echo "   Company Type: " . get_class($user->company) . "\n";
echo "   Company: {$user->company->name}\n\n";

// ============================================
// Example 2: Get Post with SerializedName
// ============================================
echo "2. Get Post (with #[SerializedName]):\n";
$post = $api->getPost(1);

echo "   Type: " . get_class($post) . "\n";
echo "   ID: {$post->id}\n";
echo "   Title: " . substr($post->title, 0, 40) . "...\n";
echo "   Author ID (mapped from 'userId'): {$post->authorId}\n\n";

// ============================================
// Example 3: Array of Posts (without ResponseType)
// ============================================
echo "3. Get Posts (array without ResponseType, first 3):\n";
$posts = $api->getPosts(3);

foreach ($posts as $index => $p) {
    // Note: Without ResponseType, these are arrays not objects
    echo "   [{$index}] {$p['title']}\n";
}
echo "\n";

// ============================================
// Example 3b: Array of Posts (with ResponseType)
// ============================================
echo "3b. Get Posts (with #[ResponseType], first 3):\n";
$postsTyped = $api->getPostsTyped(3);

foreach ($postsTyped as $index => $p) {
    // With ResponseType, these are Post objects!
    echo "   [{$index}] [" . get_class($p) . "] {$p->title}\n";
}
echo "\n";

// ============================================
// Example 4: Demonstrating Type Safety
// ============================================
echo "4. Type Safety Demo:\n";
$user = $api->getUser(2);

// IDE autocomplete works on these!
echo "   \$user->name: {$user->name}\n";
echo "   \$user->address->city: {$user->address->city}\n";
echo "   \$user->company->catchPhrase: {$user->company->catchPhrase}\n\n";

// ============================================
// Example 5: Post Comments (with ResponseType)
// ============================================
echo "5. Get Post Comments (with #[ResponseType]):\n";

$comments = $api->getPostComments(1);
foreach (array_slice($comments, 0, 2) as $comment) {
    echo "   [" . get_class($comment) . "] {$comment->name}\n";
    echo "      Email: {$comment->email}\n";
}

echo "\n=== Done ===\n";
