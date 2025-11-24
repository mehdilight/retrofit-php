<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Interceptor;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Internal\InterceptorChain;

class InterceptorTest extends TestCase
{
    public function testInterceptorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(Interceptor::class));
    }

    public function testChainInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(Chain::class));
    }

    public function testInterceptorChainClassExists(): void
    {
        $this->assertTrue(class_exists(InterceptorChain::class));
    }

    public function testChainProceedExecutesRequest(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $expectedResponse = new Response(200, 'OK', '{"data": "test"}', [], '{"data": "test"}');

        $executor = fn(Request $req) => $expectedResponse;

        $chain = new InterceptorChain($request, [], $executor);
        $response = $chain->proceed($request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testChainRequestReturnsCurrentRequest(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $executor = fn(Request $req) => new Response(200, 'OK', null);

        $chain = new InterceptorChain($request, [], $executor);

        $this->assertSame($request, $chain->request());
    }

    public function testSingleInterceptorModifiesRequest(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $capturedRequest = null;

        $executor = function (Request $req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new Response(200, 'OK', null);
        };

        $interceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('Authorization', 'Bearer token123');
                return $chain->proceed($request);
            }
        };

        $chain = new InterceptorChain($request, [$interceptor], $executor);
        $chain->proceed($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('Bearer token123', $capturedRequest->headers['Authorization']);
    }

    public function testMultipleInterceptorsExecuteInOrder(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $executionOrder = [];

        $executor = fn(Request $req) => new Response(200, 'OK', null);

        $interceptor1 = new class($executionOrder) implements Interceptor {
            public function __construct(private array &$order) {}
            public function intercept(Chain $chain): Response
            {
                $this->order[] = 'interceptor1_before';
                $response = $chain->proceed($chain->request());
                $this->order[] = 'interceptor1_after';
                return $response;
            }
        };

        $interceptor2 = new class($executionOrder) implements Interceptor {
            public function __construct(private array &$order) {}
            public function intercept(Chain $chain): Response
            {
                $this->order[] = 'interceptor2_before';
                $response = $chain->proceed($chain->request());
                $this->order[] = 'interceptor2_after';
                return $response;
            }
        };

        $chain = new InterceptorChain($request, [$interceptor1, $interceptor2], $executor);
        $chain->proceed($request);

        $this->assertSame([
            'interceptor1_before',
            'interceptor2_before',
            'interceptor2_after',
            'interceptor1_after',
        ], $executionOrder);
    }

    public function testInterceptorCanModifyResponse(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);

        $executor = fn(Request $req) => new Response(200, 'OK', '{"original": true}', [], '{"original": true}');

        $interceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $response = $chain->proceed($chain->request());
                // Return modified response
                return new Response(
                    $response->code,
                    $response->message,
                    $response->body,
                    array_merge($response->headers, ['X-Modified' => 'true']),
                    $response->rawBody
                );
            }
        };

        $chain = new InterceptorChain($request, [$interceptor], $executor);
        $response = $chain->proceed($request);

        $this->assertSame('true', $response->headers['X-Modified']);
    }

    public function testInterceptorCanShortCircuit(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $executorCalled = false;

        $executor = function (Request $req) use (&$executorCalled) {
            $executorCalled = true;
            return new Response(200, 'OK', null);
        };

        $interceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                // Return cached response without calling proceed
                return new Response(200, 'OK', '{"cached": true}', ['X-Cache' => 'HIT'], '{"cached": true}');
            }
        };

        $chain = new InterceptorChain($request, [$interceptor], $executor);
        $response = $chain->proceed($request);

        $this->assertFalse($executorCalled);
        $this->assertSame('HIT', $response->headers['X-Cache']);
    }

    public function testChainPassesModifiedRequestToNextInterceptor(): void
    {
        $request = new Request('GET', 'https://api.example.com/users', [], []);
        $capturedRequest = null;

        $executor = function (Request $req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new Response(200, 'OK', null);
        };

        $interceptor1 = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('X-First', 'first');
                return $chain->proceed($request);
            }
        };

        $interceptor2 = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                $request = $chain->request()->withHeader('X-Second', 'second');
                return $chain->proceed($request);
            }
        };

        $chain = new InterceptorChain($request, [$interceptor1, $interceptor2], $executor);
        $chain->proceed($request);

        $this->assertSame('first', $capturedRequest->headers['X-First']);
        $this->assertSame('second', $capturedRequest->headers['X-Second']);
    }
}
