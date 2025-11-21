# Retrofit PHP

A type-safe HTTP client for PHP, inspired by [Square's Retrofit](https://square.github.io/retrofit/) for Java/Kotlin.

Turn your HTTP API into a PHP interface using attributes.

## Installation

```bash
composer require phpmystic/retrofit-php
```

## Quick Start

### 1. Define your API interface

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

interface GitHubApi
{
    #[GET('/users/{user}')]
    public function getUser(#[Path('user')] string $username): array;

    #[GET('/users/{user}/repos')]
    public function listRepos(
        #[Path('user')] string $username,
        #[Query('per_page')] int $perPage = 10,
        #[Query('sort')] string $sort = 'updated'
    ): array;

    #[POST('/repos/{owner}/{repo}/issues')]
    public function createIssue(
        #[Path('owner')] string $owner,
        #[Path('repo')] string $repo,
        #[Body] array $issue
    ): array;
}
```

### 2. Create a Retrofit instance

```php
use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.github.com')
    ->client(GuzzleHttpClient::create(['timeout' => 30]))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### 3. Use your API

```php
$github = $retrofit->create(GitHubApi::class);

// GET request with path parameter
$user = $github->getUser('octocat');
echo $user['name']; // "The Octocat"

// GET request with query parameters
$repos = $github->listRepos('octocat', perPage: 5, sort: 'stars');

// POST request with body
$issue = $github->createIssue('owner', 'repo', [
    'title' => 'Bug report',
    'body' => 'Something is broken',
]);
```

## HTTP Methods

```php
#[GET('/users')]
public function getUsers(): array;

#[POST('/users')]
public function createUser(#[Body] array $data): array;

#[PUT('/users/{id}')]
public function updateUser(#[Path('id')] int $id, #[Body] array $data): array;

#[DELETE('/users/{id}')]
public function deleteUser(#[Path('id')] int $id): array;

#[PATCH('/users/{id}')]
public function patchUser(#[Path('id')] int $id, #[Body] array $data): array;

#[HEAD('/users/{id}')]
public function headUser(#[Path('id')] int $id): array;

#[OPTIONS('/users')]
public function optionsUsers(): array;
```

## URL Parameters

### Path Parameters

Replace URL segments dynamically:

```php
#[GET('/users/{user}/repos/{repo}')]
public function getRepo(
    #[Path('user')] string $user,
    #[Path('repo')] string $repo
): array;

// Calls: GET /users/octocat/repos/Hello-World
$repo = $api->getRepo('octocat', 'Hello-World');
```

### Query Parameters

Add query string parameters:

```php
#[GET('/users')]
public function searchUsers(
    #[Query('q')] string $query,
    #[Query('page')] int $page = 1,
    #[Query('per_page')] int $perPage = 10
): array;

// Calls: GET /users?q=john&page=2&per_page=20
$users = $api->searchUsers('john', page: 2, perPage: 20);
```

### Query Map

Pass multiple query parameters as an array:

```php
#[GET('/search')]
public function search(#[QueryMap] array $params): array;

// Calls: GET /search?q=test&sort=date&order=desc
$results = $api->search(['q' => 'test', 'sort' => 'date', 'order' => 'desc']);
```

## Request Body

### JSON Body

```php
#[POST('/users')]
public function createUser(#[Body] array $user): array;

$api->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
// Sends: {"name":"John Doe","email":"john@example.com"}
```

### Form Encoded

```php
use Phpmystic\RetrofitPhp\Attributes\FormUrlEncoded;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;

#[POST('/login')]
#[FormUrlEncoded]
public function login(
    #[Field('username')] string $username,
    #[Field('password')] string $password
): array;

// Sends: username=john&password=secret (application/x-www-form-urlencoded)
```

### Field Map

```php
#[POST('/login')]
#[FormUrlEncoded]
public function login(#[FieldMap] array $credentials): array;

$api->login(['username' => 'john', 'password' => 'secret']);
```

## Headers

### Static Headers

Define headers on the method:

```php
use Phpmystic\RetrofitPhp\Attributes\Headers;

#[GET('/users')]
#[Headers(
    'Accept: application/json',
    'X-Api-Version: v1'
)]
public function getUsers(): array;
```

### Dynamic Headers

Pass headers as parameters:

```php
use Phpmystic\RetrofitPhp\Attributes\Header;

#[GET('/users')]
public function getUsers(
    #[Header('Authorization')] string $token
): array;

$api->getUsers('Bearer my-token');
```

### Header Map

```php
use Phpmystic\RetrofitPhp\Attributes\HeaderMap;

#[GET('/users')]
public function getUsers(#[HeaderMap] array $headers): array;

$api->getUsers([
    'Authorization' => 'Bearer token',
    'X-Request-Id' => 'abc123',
]);
```

## HTTP Client Configuration

### Guzzle Options

```php
$client = GuzzleHttpClient::create([
    'timeout' => 30,
    'connect_timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
    ],
    'verify' => false, // Disable SSL verification (not recommended for production)
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Custom HTTP Client

Implement the `HttpClient` interface:

```php
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

class MyHttpClient implements HttpClient
{
    public function execute(Request $request): Response
    {
        // Your implementation
    }
}
```

## Converters

### JSON Converter (Default)

```php
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Custom Converter

Implement `ConverterFactory`:

```php
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

class XmlConverterFactory implements ConverterFactory
{
    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return new XmlRequestConverter();
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        return new XmlResponseConverter();
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return new StringConverter();
    }
}
```

## Complete Example

```php
<?php

require 'vendor/autoload.php';

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

interface PostsApi
{
    #[GET('/posts')]
    public function list(#[Query('_limit')] int $limit = 10): array;

    #[GET('/posts/{id}')]
    public function get(#[Path('id')] int $id): array;

    #[POST('/posts')]
    public function create(#[Body] array $post): array;

    #[PUT('/posts/{id}')]
    public function update(#[Path('id')] int $id, #[Body] array $post): array;

    #[DELETE('/posts/{id}')]
    public function delete(#[Path('id')] int $id): array;
}

// Setup
$retrofit = Retrofit::builder()
    ->baseUrl('https://jsonplaceholder.typicode.com')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(PostsApi::class);

// List posts
$posts = $api->list(5);
foreach ($posts as $post) {
    echo "- {$post['title']}\n";
}

// Get single post
$post = $api->get(1);
echo "Title: {$post['title']}\n";

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

## Requirements

- PHP 8.1+
- Guzzle 7.0+

## License

MIT
