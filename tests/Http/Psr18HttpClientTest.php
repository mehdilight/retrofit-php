<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Http;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Http\Psr18HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as NyholmResponse;

class Psr18HttpClientTest extends TestCase
{
    private Psr17Factory $psr17Factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->psr17Factory = new Psr17Factory();
    }

    public function testImplementsHttpClient(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testExecuteGetRequest(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new NyholmResponse(200, [], '{"success":true}'));

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users',
        );

        $response = $client->execute($request);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('{"success":true}', $response->rawBody);
    }

    public function testExecuteWithQueryParameters(): void
    {
        $capturedRequest = null;

        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new NyholmResponse(200, [], '[]');
            });

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users',
            query: ['page' => 1, 'limit' => 10],
        );

        $client->execute($request);

        $this->assertNotNull($capturedRequest);
        $this->assertStringContainsString('page=1', (string) $capturedRequest->getUri());
        $this->assertStringContainsString('limit=10', (string) $capturedRequest->getUri());
    }

    public function testExecutePostRequestWithJsonBody(): void
    {
        $capturedRequest = null;

        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new NyholmResponse(201, [], '{"id":123}');
            });

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'POST',
            url: 'https://api.example.com/users',
            body: '{"name":"John"}',
        );

        $response = $client->execute($request);

        $this->assertEquals(201, $response->code);
        $this->assertNotNull($capturedRequest);
        $this->assertEquals('{"name":"John"}', (string) $capturedRequest->getBody());
    }

    public function testExecuteWithHeaders(): void
    {
        $capturedRequest = null;

        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return new NyholmResponse(200, [], 'OK');
            });

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users',
            headers: [
                'Authorization' => 'Bearer token123',
                'Accept' => 'application/json',
            ],
        );

        $client->execute($request);

        $this->assertNotNull($capturedRequest);
        $this->assertEquals(['Bearer token123'], $capturedRequest->getHeader('Authorization'));
        $this->assertEquals(['application/json'], $capturedRequest->getHeader('Accept'));
    }

    public function testExecuteReturnsResponseHeaders(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new NyholmResponse(
                200,
                [
                    'Content-Type' => 'application/json',
                    'X-Custom-Header' => 'custom-value',
                ],
                '{"data":"test"}'
            ));

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/data',
        );

        $response = $client->execute($request);

        $this->assertEquals('application/json', $response->headers['Content-Type']);
        $this->assertEquals('custom-value', $response->headers['X-Custom-Header']);
    }

    public function testExecuteHandles4xxError(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new NyholmResponse(404, [], '{"error":"Not Found"}'));

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users/999',
        );

        $response = $client->execute($request);

        $this->assertEquals(404, $response->code);
        $this->assertEquals('{"error":"Not Found"}', $response->rawBody);
    }

    public function testExecuteHandles5xxError(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new NyholmResponse(500, [], '{"error":"Internal Server Error"}'));

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users',
        );

        $response = $client->execute($request);

        $this->assertEquals(500, $response->code);
        $this->assertFalse($response->isSuccessful());
    }

    public function testCreateStaticFactory(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $client = Psr18HttpClient::create($psr18Client);

        $this->assertInstanceOf(Psr18HttpClient::class, $client);
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testExecutePutRequest(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($request) {
                $this->assertEquals('PUT', $request->getMethod());
                return new NyholmResponse(200, [], '{"updated":true}');
            });

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'PUT',
            url: 'https://api.example.com/users/1',
            body: '{"name":"Jane"}',
        );

        $response = $client->execute($request);

        $this->assertEquals(200, $response->code);
    }

    public function testExecuteDeleteRequest(): void
    {
        $psr18Client = $this->createMock(ClientInterface::class);
        $psr18Client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($request) {
                $this->assertEquals('DELETE', $request->getMethod());
                return new NyholmResponse(204, [], '');
            });

        $client = new Psr18HttpClient($psr18Client, $this->psr17Factory, $this->psr17Factory);

        $request = new Request(
            method: 'DELETE',
            url: 'https://api.example.com/users/1',
        );

        $response = $client->execute($request);

        $this->assertEquals(204, $response->code);
    }
}
