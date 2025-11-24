<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\SymfonySerializerConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class SymfonySerializerConverterFactoryTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }

    public function testImplementsConverterFactory(): void
    {
        $factory = new SymfonySerializerConverterFactory($this->serializer);
        $this->assertInstanceOf(ConverterFactory::class, $factory);
    }

    public function testRequestBodyConverterReturnsConverter(): void
    {
        $factory = new SymfonySerializerConverterFactory($this->serializer);
        $converter = $factory->requestBodyConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testResponseBodyConverterReturnsConverter(): void
    {
        $factory = new SymfonySerializerConverterFactory($this->serializer);
        $converter = $factory->responseBodyConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testStringConverterReturnsConverter(): void
    {
        $factory = new SymfonySerializerConverterFactory($this->serializer);
        $converter = $factory->stringConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testWithCustomFormat(): void
    {
        $factory = new SymfonySerializerConverterFactory($this->serializer, format: 'xml');
        $this->assertInstanceOf(SymfonySerializerConverterFactory::class, $factory);
    }

    public function testWithCustomContext(): void
    {
        $context = ['groups' => ['user:read']];
        $factory = new SymfonySerializerConverterFactory($this->serializer, context: $context);
        $this->assertInstanceOf(SymfonySerializerConverterFactory::class, $factory);
    }
}
