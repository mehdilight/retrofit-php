<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Http;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Http\Request;

class RequestTest extends TestCase
{
    public function testRequestConstruction(): void
    {
        $request = new Request(
            method: 'GET',
            url: 'https://api.example.com/users',
            headers: ['Accept' => 'application/json'],
            query: ['page' => 1],
            body: null,
        );

        $this->assertSame('GET', $request->method);
        $this->assertSame('https://api.example.com/users', $request->url);
        $this->assertSame(['Accept' => 'application/json'], $request->headers);
        $this->assertSame(['page' => 1], $request->query);
        $this->assertNull($request->body);
    }

    public function testWithHeaders(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', ['Accept' => 'application/json']);
        $newRequest = $request->withHeaders(['Authorization' => 'Bearer token']);

        $this->assertSame(['Accept' => 'application/json'], $request->headers);
        $this->assertSame([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer token',
        ], $newRequest->headers);
    }

    public function testWithBody(): void
    {
        $request = new Request('POST', 'https://api.example.com/users');
        $body = ['name' => 'John'];
        $newRequest = $request->withBody($body);

        $this->assertNull($request->body);
        $this->assertSame($body, $newRequest->body);
    }

    public function testWithQuery(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], ['page' => 1]);
        $newRequest = $request->withQuery(['limit' => 10]);

        $this->assertSame(['page' => 1], $request->query);
        $this->assertSame(['page' => 1, 'limit' => 10], $newRequest->query);
    }

    public function testImmutability(): void
    {
        $request = new Request('GET', 'https://api.example.com/users');

        $this->assertNotSame($request, $request->withHeaders(['X-Test' => 'value']));
        $this->assertNotSame($request, $request->withBody(['test' => 'data']));
        $this->assertNotSame($request, $request->withQuery(['page' => 1]));
    }
}
