<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Header;
use Phpmystic\RetrofitPhp\Attributes\Headers;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

/**
 * Example showing header usage
 * Using httpbin.org to echo back our requests
 */
interface HttpBinApi
{
    // Static headers defined on method
    #[GET('/headers')]
    #[Headers(
        'X-Custom-Header: my-value',
        'X-Another-Header: another-value'
    )]
    public function getWithStaticHeaders(): array;

    // Dynamic header from parameter
    #[GET('/headers')]
    public function getWithDynamicHeader(
        #[Header('Authorization')] string $token
    ): array;

    // Mix of static and dynamic headers
    #[POST('/post')]
    #[Headers('Content-Type: application/json')]
    public function postWithMixedHeaders(
        #[Header('X-Request-Id')] string $requestId,
        #[Body] array $data
    ): array;
}

// Create Retrofit instance
$retrofit = Retrofit::builder()
    ->baseUrl('https://httpbin.org')
    ->client(GuzzleHttpClient::create(['timeout' => 30]))
    ->addConverterFactory(new JsonConverterFactory())
    ->build();

$api = $retrofit->create(HttpBinApi::class);

echo "=== Headers Example ===\n\n";

// Static headers
echo "1. Request with static headers...\n";
$response = $api->getWithStaticHeaders();
echo "   Headers received by server:\n";
foreach ($response['headers'] as $name => $value) {
    if (str_starts_with($name, 'X-')) {
        echo "   - {$name}: {$value}\n";
    }
}
echo "\n";

// Dynamic header
echo "2. Request with dynamic Authorization header...\n";
$response = $api->getWithDynamicHeader('Bearer my-secret-token');
echo "   Authorization: {$response['headers']['Authorization']}\n\n";

// Mixed headers
echo "3. POST with mixed headers...\n";
$response = $api->postWithMixedHeaders(
    'request-123',
    ['message' => 'Hello World']
);
echo "   X-Request-Id: {$response['headers']['X-Request-Id']}\n";
echo "   Content-Type: {$response['headers']['Content-Type']}\n";
echo "   Body sent: " . json_encode($response['json']) . "\n";
