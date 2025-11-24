<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Async;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Contracts\Call;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Internal\DefaultCall;
use GuzzleHttp\Promise\PromiseInterface;

class AsyncCallTest extends TestCase
{
    public function testExecuteAsyncReturnsPromise(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"id":1}'),
        ]);
        $httpClient = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $call = new DefaultCall(
            $httpClient,
            new Request('GET', 'https://api.example.com/users/1'),
            null,
            null,
        );

        $promise = $call->executeAsync();

        $this->assertInstanceOf(PromiseInterface::class, $promise);
    }

    public function testExecuteAsyncResolvesWithResponse(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"id":1,"name":"John"}'),
        ]);
        $httpClient = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $call = new DefaultCall(
            $httpClient,
            new Request('GET', 'https://api.example.com/users/1'),
            null,
            null,
        );

        $promise = $call->executeAsync();
        $response = $promise->wait();

        $this->assertSame(200, $response->code);
        $this->assertSame('{"id":1,"name":"John"}', $response->rawBody);
    }

    public function testExecuteAsyncWithCallback(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"success":true}'),
        ]);
        $httpClient = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $call = new DefaultCall(
            $httpClient,
            new Request('GET', 'https://api.example.com/test'),
            null,
            null,
        );

        $result = null;
        $promise = $call->executeAsync()->then(function ($response) use (&$result) {
            $result = $response;
        });

        $promise->wait();

        $this->assertNotNull($result);
        $this->assertSame(200, $result->code);
    }

    public function testMultipleAsyncCallsInParallel(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], '{"id":1}'),
            new GuzzleResponse(200, [], '{"id":2}'),
            new GuzzleResponse(200, [], '{"id":3}'),
        ]);
        $httpClient = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $calls = [
            new DefaultCall($httpClient, new Request('GET', 'https://api.example.com/1'), null, null),
            new DefaultCall($httpClient, new Request('GET', 'https://api.example.com/2'), null, null),
            new DefaultCall($httpClient, new Request('GET', 'https://api.example.com/3'), null, null),
        ];

        $promises = array_map(fn($call) => $call->executeAsync(), $calls);
        $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);

        $this->assertCount(3, $responses);
        $this->assertSame('{"id":1}', $responses[0]->rawBody);
        $this->assertSame('{"id":2}', $responses[1]->rawBody);
        $this->assertSame('{"id":3}', $responses[2]->rawBody);
    }

    public function testExecuteAsyncHandlesError(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(404, [], '{"error":"Not found"}'),
        ]);
        $httpClient = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $call = new DefaultCall(
            $httpClient,
            new Request('GET', 'https://api.example.com/notfound'),
            null,
            null,
        );

        $promise = $call->executeAsync();
        $response = $promise->wait();

        $this->assertSame(404, $response->code);
        $this->assertFalse($response->isSuccessful());
    }
}
