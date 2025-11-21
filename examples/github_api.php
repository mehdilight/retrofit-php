<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;

/**
 * GitHub API Service Interface
 */
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

    #[GET('/repos/{owner}/{repo}')]
    public function getRepo(
        #[Path('owner')] string $owner,
        #[Path('repo')] string $repo
    ): array;
}

// Create Retrofit instance
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.github.com')
    ->client(GuzzleHttpClient::create([
        'timeout' => 30,
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Retrofit-PHP-Example',
        ],
    ]))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

// Create API service
$github = $retrofit->create(GitHubApi::class);

echo "=== GitHub API Example ===\n\n";

// Get user info
echo "1. Getting user info for 'octocat'...\n";
$user = $github->getUser('octocat');
echo "   Name: {$user['name']}\n";
echo "   Location: {$user['location']}\n";
echo "   Public repos: {$user['public_repos']}\n\n";

// List repos
echo "2. Listing repos for 'octocat' (first 5)...\n";
$repos = $github->listRepos('octocat', 5, 'stars');
foreach ($repos as $repo) {
    echo "   - {$repo['name']}: ⭐ {$repo['stargazers_count']}\n";
}
echo "\n";

// Get specific repo
echo "3. Getting repo details for 'octocat/Hello-World'...\n";
$repo = $github->getRepo('octocat', 'Hello-World');
echo "   Full name: {$repo['full_name']}\n";
echo "   Description: {$repo['description']}\n";
echo "   Stars: {$repo['stargazers_count']}\n";
echo "   Forks: {$repo['forks_count']}\n";
