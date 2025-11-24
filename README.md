# Retrofit PHP

A type-safe HTTP client for PHP, inspired by [Square's Retrofit](https://square.github.io/retrofit/) for Java/Kotlin.

Turn your HTTP API into a PHP interface using attributes.

## Features

- ✅ **Type-Safe API Definitions** - Define APIs as PHP interfaces with attributes
- ✅ **HTTP Methods** - Support for GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS
- ✅ **URL Parameters** - Path parameters, query parameters, and query maps
- ✅ **Request Body** - JSON, form-encoded, and multipart requests
- ✅ **Headers** - Static and dynamic headers
- ✅ **Converters** - JSON, XML, and custom converters
- ✅ **DTO Hydration** - Automatic object mapping with nested objects and field name mapping
- ✅ **Async Requests** - Promise-based async operations with Guzzle
- ✅ **Retry Policies** - Automatic retries with exponential backoff
- ✅ **Response Caching** - TTL-based caching with pluggable cache backends
- ✅ **Interceptors** - Request/response modification and logging
- ✅ **Timeouts** - Per-endpoint timeout configuration
- ✅ **File Handling** - Streaming uploads/downloads with progress tracking
- ✅ **PSR-7 & PSR-18 Compliant** - Full PSR standards support
- ✅ **XML Support** - Built-in XML serialization/deserialization
- ✅ **Symfony Serializer** - Advanced serialization with groups and contexts

## Installation

```bash
composer require phpmystic/retrofit-php
```

## Quick Start

### 1. Define your API interface

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
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

// Or use TypedJsonConverterFactory for automatic DTO hydration
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.github.com')
    ->client(GuzzleHttpClient::create(['timeout' => 30]))
    ->addConverterFactory(new TypedJsonConverterFactory())  // Enables DTO hydration
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

## Documentation

### Core Concepts
- [HTTP Methods](docs/http-methods.md) - GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS
- [URL Parameters](docs/parameters.md) - Path parameters, query parameters, and query maps
- [Request Body](docs/request-body.md) - JSON, form-encoded, and multipart requests
- [Headers](docs/headers.md) - Static and dynamic header configuration

### Configuration
- [HTTP Client Configuration](docs/client-configuration.md) - Guzzle options and custom clients
- [Converters](docs/converters.md) - JSON, XML, Symfony Serializer, DTO hydration, and custom converters
- [Timeouts](docs/timeouts.md) - Per-endpoint timeout configuration

### Advanced Features
- [Async Requests](docs/async.md) - Promise-based async operations and parallel requests
- [Retry Policies](docs/retry.md) - Automatic retries with backoff strategies
- [Response Caching](docs/caching.md) - TTL-based caching with custom backends
- [Interceptors](docs/interceptors.md) - Request/response modification and logging
- [File Handling](docs/file-handling.md) - Streaming uploads/downloads with progress tracking

### Examples
- [Complete Examples](docs/examples.md) - Full working examples with all features

## Requirements

- PHP 8.1+
- Guzzle 7.0+

**Optional Dependencies:**
- PSR-18 HTTP Client: `psr/http-client` and a PSR-18 implementation (e.g., `symfony/http-client`, `nyholm/psr7`)
- XML Support: Built-in (uses PHP's SimpleXML)
- Symfony Serializer: `symfony/serializer` ^6.0 or ^7.0

## Support

If you find this project helpful, consider supporting its development!

<a href="https://buymeacoffee.com/phpmystic" target="_blank">
  <img src="https://media4.giphy.com/media/v1.Y2lkPTc5MGI3NjExZmdnOWMxcm5oNDUwbjdnbm83bDNncjB3czU3NzBvdHFzbWExZjN5dyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/7kZE0z52Sd9zSESzDA/giphy.gif" alt="Buy Me A Coffee" height="60">
</a>

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-yellow.svg?style=flat&logo=buy-me-a-coffee)](https://buymeacoffee.com/phpmystic)

## License

MIT
