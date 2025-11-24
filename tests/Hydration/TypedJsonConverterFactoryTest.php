<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use ReflectionNamedType;

class TypedJsonConverterFactoryTest extends TestCase
{
    private TypedJsonConverterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TypedJsonConverterFactory();
    }

    public function testImplementsConverterFactory(): void
    {
        $this->assertInstanceOf(ConverterFactory::class, $this->factory);
    }

    public function testRequestBodyConverterReturnsConverter(): void
    {
        $converter = $this->factory->requestBodyConverter(null);
        $this->assertNotNull($converter);
    }

    public function testResponseBodyConverterReturnsConverter(): void
    {
        $converter = $this->factory->responseBodyConverter(null);
        $this->assertNotNull($converter);
    }

    public function testStringConverterReturnsConverter(): void
    {
        $converter = $this->factory->stringConverter(null);
        $this->assertNotNull($converter);
    }
}
