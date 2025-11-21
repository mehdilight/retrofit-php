<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\JsonResponseConverter;
use Phpmystic\RetrofitPhp\Contracts\Converter;

class JsonResponseConverterTest extends TestCase
{
    private JsonResponseConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsonResponseConverter();
    }

    public function testImplementsConverter(): void
    {
        $this->assertInstanceOf(Converter::class, $this->converter);
    }

    public function testConvertJsonObject(): void
    {
        $json = '{"name":"John","email":"john@example.com"}';
        $result = $this->converter->convert($json);

        $this->assertSame(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testConvertJsonArray(): void
    {
        $json = '[{"id":1},{"id":2}]';
        $result = $this->converter->convert($json);

        $this->assertSame([['id' => 1], ['id' => 2]], $result);
    }

    public function testConvertNestedJson(): void
    {
        $json = '{"user":{"name":"John","age":30},"roles":["admin","user"]}';
        $result = $this->converter->convert($json);

        $this->assertSame([
            'user' => ['name' => 'John', 'age' => 30],
            'roles' => ['admin', 'user'],
        ], $result);
    }

    public function testConvertEmptyObject(): void
    {
        $result = $this->converter->convert('{}');
        $this->assertSame([], $result);
    }

    public function testConvertEmptyArray(): void
    {
        $result = $this->converter->convert('[]');
        $this->assertSame([], $result);
    }

    public function testConvertNull(): void
    {
        $result = $this->converter->convert('null');
        $this->assertNull($result);
    }

    public function testConvertString(): void
    {
        $result = $this->converter->convert('"hello"');
        $this->assertSame('hello', $result);
    }

    public function testConvertInteger(): void
    {
        $result = $this->converter->convert('42');
        $this->assertSame(42, $result);
    }

    public function testConvertBoolean(): void
    {
        $this->assertTrue($this->converter->convert('true'));
        $this->assertFalse($this->converter->convert('false'));
    }

    public function testConvertWithUnicodeCharacters(): void
    {
        $json = '{"message":"Hello 世界"}';
        $result = $this->converter->convert($json);

        $this->assertSame(['message' => 'Hello 世界'], $result);
    }

    public function testConvertEmptyString(): void
    {
        $result = $this->converter->convert('');
        $this->assertNull($result);
    }

    public function testConvertInvalidJsonReturnsNull(): void
    {
        $result = $this->converter->convert('invalid json');
        $this->assertNull($result);
    }

    public function testConvertNonStringReturnsAsIs(): void
    {
        $data = ['already' => 'decoded'];
        $result = $this->converter->convert($data);

        $this->assertSame($data, $result);
    }
}
