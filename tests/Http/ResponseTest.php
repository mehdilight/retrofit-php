<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Http;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Http\Response;

class ResponseTest extends TestCase
{
    public function testResponseConstruction(): void
    {
        $response = new Response(
            code: 200,
            message: 'OK',
            body: ['id' => 1],
            headers: ['Content-Type' => 'application/json'],
            rawBody: '{"id":1}',
        );

        $this->assertSame(200, $response->code);
        $this->assertSame('OK', $response->message);
        $this->assertSame(['id' => 1], $response->body);
        $this->assertSame(['Content-Type' => 'application/json'], $response->headers);
        $this->assertSame('{"id":1}', $response->rawBody);
    }

    public function testIsSuccessful(): void
    {
        $this->assertTrue((new Response(200, 'OK', null))->isSuccessful());
        $this->assertTrue((new Response(201, 'Created', null))->isSuccessful());
        $this->assertTrue((new Response(204, 'No Content', null))->isSuccessful());
        $this->assertTrue((new Response(299, 'Custom', null))->isSuccessful());

        $this->assertFalse((new Response(199, 'Info', null))->isSuccessful());
        $this->assertFalse((new Response(300, 'Redirect', null))->isSuccessful());
        $this->assertFalse((new Response(400, 'Bad Request', null))->isSuccessful());
        $this->assertFalse((new Response(500, 'Server Error', null))->isSuccessful());
    }

    public function testSuccessFactory(): void
    {
        $response = Response::success(['id' => 1]);

        $this->assertSame(200, $response->code);
        $this->assertSame('OK', $response->message);
        $this->assertSame(['id' => 1], $response->body);
        $this->assertTrue($response->isSuccessful());
    }

    public function testSuccessFactoryWithCustomCode(): void
    {
        $response = Response::success(['id' => 1], 201);

        $this->assertSame(201, $response->code);
        $this->assertTrue($response->isSuccessful());
    }

    public function testErrorFactory(): void
    {
        $response = Response::error(404, 'Not Found', '{"error":"Resource not found"}');

        $this->assertSame(404, $response->code);
        $this->assertSame('Not Found', $response->message);
        $this->assertNull($response->body);
        $this->assertSame('{"error":"Resource not found"}', $response->rawBody);
        $this->assertFalse($response->isSuccessful());
    }
}
