<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Http\Request;

class GuzzleHttpClientTest extends TestCase
{
    public function testImplementsHttpClient(): void
    {
        $client = new GuzzleHttpClient(new Client());
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testExecuteGetRequest(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"id":1,"name":"John"}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/users/1');
        $response = $client->execute($request);

        $this->assertSame(200, $response->code);
        $this->assertSame('{"id":1,"name":"John"}', $response->rawBody);
    }

    public function testExecuteGetRequestWithQuery(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '[]'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleHttpClient(new Client(['handler' => $handlerStack]));

        $request = new Request('GET', 'https://api.example.com/users', [], ['page' => 1, 'limit' => 10]);
        $response = $client->execute($request);

        $this->assertSame(200, $response->code);
    }

    public function testExecutePostRequestWithJsonBody(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(201, [], '{"id":1}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request(
            'POST',
            'https://api.example.com/users',
            ['Content-Type' => 'application/json'],
            [],
            '{"name":"John"}'
        );
        $response = $client->execute($request);

        $this->assertSame(201, $response->code);
    }

    public function testExecutePostRequestWithFormData(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"success":true}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request(
            'POST',
            'https://api.example.com/login',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            [],
            ['username' => 'john', 'password' => 'secret']
        );
        $response = $client->execute($request);

        $this->assertSame(200, $response->code);
    }

    public function testExecutePutRequest(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"id":1,"name":"Updated"}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('PUT', 'https://api.example.com/users/1', [], [], '{"name":"Updated"}');
        $response = $client->execute($request);

        $this->assertSame(200, $response->code);
    }

    public function testExecuteDeleteRequest(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(204, [], ''),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('DELETE', 'https://api.example.com/users/1');
        $response = $client->execute($request);

        $this->assertSame(204, $response->code);
    }

    public function testExecuteWithHeaders(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request(
            'GET',
            'https://api.example.com/users',
            ['Authorization' => 'Bearer token123', 'Accept' => 'application/json']
        );
        $response = $client->execute($request);

        $this->assertSame(200, $response->code);
    }

    public function testExecuteReturnsResponseHeaders(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['X-Custom-Header' => 'value', 'Content-Type' => 'application/json'], '{}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/users');
        $response = $client->execute($request);

        $this->assertArrayHasKey('X-Custom-Header', $response->headers);
        $this->assertArrayHasKey('Content-Type', $response->headers);
    }

    public function testExecuteHandles4xxError(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(404, [], '{"error":"Not found"}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/users/999');
        $response = $client->execute($request);

        $this->assertSame(404, $response->code);
        $this->assertSame('{"error":"Not found"}', $response->rawBody);
        $this->assertFalse($response->isSuccessful());
    }

    public function testExecuteHandles5xxError(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(500, [], '{"error":"Internal server error"}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/users');
        $response = $client->execute($request);

        $this->assertSame(500, $response->code);
        $this->assertFalse($response->isSuccessful());
    }

    public function testCreateWithDefaults(): void
    {
        $client = GuzzleHttpClient::create();
        $this->assertInstanceOf(GuzzleHttpClient::class, $client);
    }

    public function testCreateWithOptions(): void
    {
        $client = GuzzleHttpClient::create(['timeout' => 30]);
        $this->assertInstanceOf(GuzzleHttpClient::class, $client);
    }
}
