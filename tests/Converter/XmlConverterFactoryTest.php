<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\XmlConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\Converter;

class XmlConverterFactoryTest extends TestCase
{
    public function testImplementsConverterFactory(): void
    {
        $factory = new XmlConverterFactory();
        $this->assertInstanceOf(ConverterFactory::class, $factory);
    }

    public function testRequestBodyConverterReturnsConverter(): void
    {
        $factory = new XmlConverterFactory();
        $converter = $factory->requestBodyConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testResponseBodyConverterReturnsConverter(): void
    {
        $factory = new XmlConverterFactory();
        $converter = $factory->responseBodyConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testStringConverterReturnsConverter(): void
    {
        $factory = new XmlConverterFactory();
        $converter = $factory->stringConverter(null);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testCustomRootElement(): void
    {
        $factory = new XmlConverterFactory(rootElement: 'CustomRoot');
        $this->assertInstanceOf(XmlConverterFactory::class, $factory);
    }
}
