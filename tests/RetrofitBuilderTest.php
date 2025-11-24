<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\RetrofitBuilder;

class RetrofitBuilderTest extends TestCase
{
    public function testBuilderCreatesRetrofitInstance(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($httpClient)
            ->build();

        $this->assertInstanceOf(Retrofit::class, $retrofit);
        $this->assertSame('https://api.example.com', $retrofit->baseUrl());
    }

    public function testBuilderTrimsTrailingSlash(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com/')
            ->client($httpClient)
            ->build();

        $this->assertSame('https://api.example.com', $retrofit->baseUrl());
    }

    public function testBuilderThrowsWithoutBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL required');

        $httpClient = $this->createMock(HttpClient::class);

        Retrofit::builder()
            ->client($httpClient)
            ->build();
    }

    public function testBuilderThrowsWithoutClient(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP client required');

        Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->build();
    }

    public function testBuilderReturnsNewInstance(): void
    {
        $builder = new RetrofitBuilder();
        $this->assertInstanceOf(RetrofitBuilder::class, $builder);
    }

    public function testAddInterceptorReturnsSelf(): void
    {
        $interceptor = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                return $chain->proceed($chain->request());
            }
        };

        $builder = Retrofit::builder();
        $result = $builder->addInterceptor($interceptor);

        $this->assertSame($builder, $result);
    }

    public function testBuilderAcceptsMultipleInterceptors(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $interceptor1 = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                return $chain->proceed($chain->request());
            }
        };

        $interceptor2 = new class implements Interceptor {
            public function intercept(Chain $chain): Response
            {
                return $chain->proceed($chain->request());
            }
        };

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($httpClient)
            ->addInterceptor($interceptor1)
            ->addInterceptor($interceptor2)
            ->build();

        $this->assertInstanceOf(Retrofit::class, $retrofit);
    }
}
