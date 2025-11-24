<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\SymfonySerializerRequestConverter;
use Phpmystic\RetrofitPhp\Converter\SymfonySerializerResponseConverter;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class UserDto
{
    public function __construct(
        public string $name = '',
        public int $age = 0,
    ) {}
}

class SymfonySerializerConverterTest extends TestCase
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

    // Request Converter Tests

    public function testRequestConverterImplementsConverter(): void
    {
        $converter = new SymfonySerializerRequestConverter($this->serializer);
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testConvertArrayToJson(): void
    {
        $converter = new SymfonySerializerRequestConverter($this->serializer);
        $json = $converter->convert(['name' => 'John', 'age' => 30]);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('John', $decoded['name']);
        $this->assertEquals(30, $decoded['age']);
    }

    public function testConvertObjectToJson(): void
    {
        $converter = new SymfonySerializerRequestConverter($this->serializer);
        $user = new UserDto('Alice', 25);

        $json = $converter->convert($user);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('Alice', $decoded['name']);
        $this->assertEquals(25, $decoded['age']);
    }

    public function testConvertWithCustomFormat(): void
    {
        $this->serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder(), new \Symfony\Component\Serializer\Encoder\XmlEncoder()]
        );

        $converter = new SymfonySerializerRequestConverter($this->serializer, format: 'xml');
        $xml = $converter->convert(['name' => 'Bob']);

        $this->assertStringContainsString('<response>', $xml);
        $this->assertStringContainsString('<name>Bob</name>', $xml);
    }

    public function testConvertNestedObject(): void
    {
        $converter = new SymfonySerializerRequestConverter($this->serializer);
        $data = [
            'user' => [
                'name' => 'Charlie',
                'contact' => [
                    'email' => 'charlie@example.com',
                ],
            ],
        ];

        $json = $converter->convert($data);

        $decoded = json_decode($json, true);
        $this->assertEquals('Charlie', $decoded['user']['name']);
        $this->assertEquals('charlie@example.com', $decoded['user']['contact']['email']);
    }

    // Response Converter Tests

    public function testResponseConverterImplementsConverter(): void
    {
        $converter = new SymfonySerializerResponseConverter($this->serializer);
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testConvertJsonToArray(): void
    {
        $converter = new SymfonySerializerResponseConverter($this->serializer);
        $json = '{"name":"Diana","age":28}';

        $result = $converter->convert($json);

        $this->assertIsArray($result);
        $this->assertEquals('Diana', $result['name']);
        $this->assertEquals(28, $result['age']);
    }

    public function testConvertJsonToObject(): void
    {
        $converter = new SymfonySerializerResponseConverter(
            $this->serializer,
            targetType: UserDto::class
        );
        $json = '{"name":"Eve","age":32}';

        $result = $converter->convert($json);

        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertEquals('Eve', $result->name);
        $this->assertEquals(32, $result->age);
    }

    public function testConvertEmptyJson(): void
    {
        $converter = new SymfonySerializerResponseConverter($this->serializer);
        $json = '{}';

        $result = $converter->convert($json);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConvertInvalidJsonReturnsNull(): void
    {
        $converter = new SymfonySerializerResponseConverter($this->serializer);
        $json = '{invalid json}';

        $result = $converter->convert($json);

        $this->assertNull($result);
    }

    public function testConvertNonStringReturnsAsIs(): void
    {
        $converter = new SymfonySerializerResponseConverter($this->serializer);
        $input = ['already' => 'array'];

        $result = $converter->convert($input);

        $this->assertEquals($input, $result);
    }

    public function testConvertArrayOfObjects(): void
    {
        // Symfony Serializer requires ArrayDenormalizer for arrays
        $this->serializer = new Serializer(
            [new \Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        $converter = new SymfonySerializerResponseConverter(
            $this->serializer,
            targetType: UserDto::class . '[]'
        );
        $json = '[{"name":"Frank","age":40},{"name":"Grace","age":35}]';

        $result = $converter->convert($json);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserDto::class, $result[0]);
        $this->assertEquals('Frank', $result[0]->name);
        $this->assertEquals('Grace', $result[1]->name);
    }

    public function testConvertWithCustomContext(): void
    {
        $converter = new SymfonySerializerRequestConverter(
            $this->serializer,
            context: ['json_encode_options' => JSON_PRETTY_PRINT]
        );
        $json = $converter->convert(['key' => 'value']);

        $this->assertStringContainsString("\n", $json); // Pretty printed
    }
}
