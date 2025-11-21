<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\JsonRequestConverter;
use Phpmystic\RetrofitPhp\Contracts\Converter;

class JsonRequestConverterTest extends TestCase
{
    private JsonRequestConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsonRequestConverter();
    }

    public function testImplementsConverter(): void
    {
        $this->assertInstanceOf(Converter::class, $this->converter);
    }

    public function testConvertArray(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $result = $this->converter->convert($data);

        $this->assertSame('{"name":"John","email":"john@example.com"}', $result);
    }

    public function testConvertNestedArray(): void
    {
        $data = [
            'user' => ['name' => 'John', 'age' => 30],
            'roles' => ['admin', 'user'],
        ];
        $result = $this->converter->convert($data);

        $this->assertSame('{"user":{"name":"John","age":30},"roles":["admin","user"]}', $result);
    }

    public function testConvertEmptyArray(): void
    {
        $result = $this->converter->convert([]);
        $this->assertSame('[]', $result);
    }

    public function testConvertNull(): void
    {
        $result = $this->converter->convert(null);
        $this->assertSame('null', $result);
    }

    public function testConvertString(): void
    {
        $result = $this->converter->convert('hello');
        $this->assertSame('"hello"', $result);
    }

    public function testConvertInteger(): void
    {
        $result = $this->converter->convert(42);
        $this->assertSame('42', $result);
    }

    public function testConvertBoolean(): void
    {
        $this->assertSame('true', $this->converter->convert(true));
        $this->assertSame('false', $this->converter->convert(false));
    }

    public function testConvertObjectWithPublicProperties(): void
    {
        $obj = new class {
            public string $name = 'John';
            public int $age = 30;
        };

        $result = $this->converter->convert($obj);
        $this->assertSame('{"name":"John","age":30}', $result);
    }

    public function testConvertStdClass(): void
    {
        $obj = new \stdClass();
        $obj->name = 'John';
        $obj->email = 'john@example.com';

        $result = $this->converter->convert($obj);
        $this->assertSame('{"name":"John","email":"john@example.com"}', $result);
    }

    public function testConvertWithUnicodeCharacters(): void
    {
        $data = ['message' => 'Hello 世界'];
        $result = $this->converter->convert($data);

        $this->assertSame('{"message":"Hello 世界"}', $result);
    }
}
