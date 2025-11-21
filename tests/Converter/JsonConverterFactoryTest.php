<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

class JsonConverterFactoryTest extends TestCase
{
    private JsonConverterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new JsonConverterFactory();
    }

    public function testImplementsConverterFactory(): void
    {
        $this->assertInstanceOf(ConverterFactory::class, $this->factory);
    }

    public function testRequestBodyConverterReturnsConverter(): void
    {
        $converter = $this->factory->requestBodyConverter(null);
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testResponseBodyConverterReturnsConverter(): void
    {
        $converter = $this->factory->responseBodyConverter(null);
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testStringConverterReturnsConverter(): void
    {
        $converter = $this->factory->stringConverter(null);
        $this->assertInstanceOf(Converter::class, $converter);
    }
}
