<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\JsonStringConverter;
use Phpmystic\RetrofitPhp\Contracts\Converter;

class JsonStringConverterTest extends TestCase
{
    private JsonStringConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsonStringConverter();
    }

    public function testImplementsConverter(): void
    {
        $this->assertInstanceOf(Converter::class, $this->converter);
    }

    public function testConvertString(): void
    {
        $result = $this->converter->convert('hello');
        $this->assertSame('hello', $result);
    }

    public function testConvertInteger(): void
    {
        $result = $this->converter->convert(42);
        $this->assertSame('42', $result);
    }

    public function testConvertFloat(): void
    {
        $result = $this->converter->convert(3.14);
        $this->assertSame('3.14', $result);
    }

    public function testConvertBoolean(): void
    {
        $this->assertSame('true', $this->converter->convert(true));
        $this->assertSame('false', $this->converter->convert(false));
    }

    public function testConvertNull(): void
    {
        $result = $this->converter->convert(null);
        $this->assertSame('', $result);
    }

    public function testConvertArray(): void
    {
        $result = $this->converter->convert(['a', 'b']);
        $this->assertSame('["a","b"]', $result);
    }

    public function testConvertObject(): void
    {
        $obj = new \stdClass();
        $obj->name = 'John';

        $result = $this->converter->convert($obj);
        $this->assertSame('{"name":"John"}', $result);
    }
}
