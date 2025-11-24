<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Async;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use GuzzleHttp\Promise\PromiseInterface;

class GuzzleHttpClientAsyncTest extends TestCase
{
    public function testExecuteAsyncReturnsPromise(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/test');
        $promise = $client->executeAsync($request);

        $this->assertInstanceOf(PromiseInterface::class, $promise);
    }

    public function testExecuteAsyncGetRequest(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"data":"test"}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request('GET', 'https://api.example.com/test');
        $promise = $client->executeAsync($request);
        $response = $promise->wait();

        $this->assertSame(200, $response->code);
        $this->assertSame('{"data":"test"}', $response->rawBody);
    }

    public function testExecuteAsyncPostRequest(): void
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
        $promise = $client->executeAsync($request);
        $response = $promise->wait();

        $this->assertSame(201, $response->code);
    }

    public function testExecuteAsyncWithHeaders(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['X-Custom' => 'value'], '{}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $request = new Request(
            'GET',
            'https://api.example.com/test',
            ['Authorization' => 'Bearer token']
        );
        $promise = $client->executeAsync($request);
        $response = $promise->wait();

        $this->assertSame(200, $response->code);
        $this->assertArrayHasKey('X-Custom', $response->headers);
    }

    public function testParallelAsyncRequests(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"user":1}'),
            new GuzzleResponse(200, [], '{"user":2}'),
        ]);
        $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $promise1 = $client->executeAsync(new Request('GET', 'https://api.example.com/user/1'));
        $promise2 = $client->executeAsync(new Request('GET', 'https://api.example.com/user/2'));

        $responses = \GuzzleHttp\Promise\Utils::unwrap([$promise1, $promise2]);

        $this->assertCount(2, $responses);
        $this->assertSame('{"user":1}', $responses[0]->rawBody);
        $this->assertSame('{"user":2}', $responses[1]->rawBody);
    }
}
