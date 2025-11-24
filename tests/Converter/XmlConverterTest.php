<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\XmlRequestConverter;
use Phpmystic\RetrofitPhp\Converter\XmlResponseConverter;
use Phpmystic\RetrofitPhp\Contracts\Converter;

class XmlConverterTest extends TestCase
{
    // Request Converter Tests

    public function testRequestConverterImplementsConverter(): void
    {
        $converter = new XmlRequestConverter();
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testConvertArrayToXml(): void
    {
        $converter = new XmlRequestConverter();
        $xml = $converter->convert(['name' => 'John', 'age' => 30]);

        $this->assertStringContainsString('<root>', $xml);
        $this->assertStringContainsString('<name>John</name>', $xml);
        $this->assertStringContainsString('<age>30</age>', $xml);
        $this->assertStringContainsString('</root>', $xml);
    }

    public function testConvertNestedArrayToXml(): void
    {
        $converter = new XmlRequestConverter();
        $data = [
            'user' => [
                'name' => 'John',
                'address' => [
                    'city' => 'New York',
                    'country' => 'USA',
                ],
            ],
        ];

        $xml = $converter->convert($data);

        $this->assertStringContainsString('<user>', $xml);
        $this->assertStringContainsString('<name>John</name>', $xml);
        $this->assertStringContainsString('<address>', $xml);
        $this->assertStringContainsString('<city>New York</city>', $xml);
        $this->assertStringContainsString('<country>USA</country>', $xml);
    }

    public function testConvertWithCustomRootElement(): void
    {
        $converter = new XmlRequestConverter(rootElement: 'Order');
        $xml = $converter->convert(['id' => 123, 'total' => 99.99]);

        $this->assertStringContainsString('<Order>', $xml);
        $this->assertStringContainsString('</Order>', $xml);
        $this->assertStringNotContainsString('<root>', $xml);
    }

    public function testConvertArrayWithNumericKeys(): void
    {
        $converter = new XmlRequestConverter();
        $xml = $converter->convert(['items' => ['Apple', 'Banana', 'Orange']]);

        $this->assertStringContainsString('<items>', $xml);
        $this->assertStringContainsString('<item>Apple</item>', $xml);
        $this->assertStringContainsString('<item>Banana</item>', $xml);
        $this->assertStringContainsString('<item>Orange</item>', $xml);
    }

    public function testConvertObjectWithPublicProperties(): void
    {
        $converter = new XmlRequestConverter();

        $obj = new class {
            public string $name = 'Test';
            public int $value = 42;
        };

        $xml = $converter->convert($obj);

        $this->assertStringContainsString('<name>Test</name>', $xml);
        $this->assertStringContainsString('<value>42</value>', $xml);
    }

    public function testConvertWithSpecialCharacters(): void
    {
        $converter = new XmlRequestConverter();
        $xml = $converter->convert(['text' => 'Hello & <world>']);

        $this->assertStringContainsString('<text>Hello &amp; &lt;world&gt;</text>', $xml);
    }

    // Response Converter Tests

    public function testResponseConverterImplementsConverter(): void
    {
        $converter = new XmlResponseConverter();
        $this->assertInstanceOf(Converter::class, $converter);
    }

    public function testConvertXmlToArray(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<root><name>John</name><age>30</age></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('30', $result['age']);
    }

    public function testConvertNestedXmlToArray(): void
    {
        $converter = new XmlResponseConverter();
        $xml = <<<XML
<root>
    <user>
        <name>John</name>
        <address>
            <city>New York</city>
            <country>USA</country>
        </address>
    </user>
</root>
XML;

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertEquals('John', $result['user']['name']);
        $this->assertEquals('New York', $result['user']['address']['city']);
        $this->assertEquals('USA', $result['user']['address']['country']);
    }

    public function testConvertXmlWithMultipleElements(): void
    {
        $converter = new XmlResponseConverter();
        $xml = <<<XML
<root>
    <items>
        <item>Apple</item>
        <item>Banana</item>
        <item>Orange</item>
    </items>
</root>
XML;

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']['item']);
    }

    public function testConvertXmlWithAttributes(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<root><item id="123" type="product">Widget</item></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('item', $result);
    }

    public function testConvertEmptyXml(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<root></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConvertInvalidXmlReturnsNull(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<invalid>xml<without></closing>';

        $result = $converter->convert($xml);

        $this->assertNull($result);
    }

    public function testConvertNonStringReturnsAsIs(): void
    {
        $converter = new XmlResponseConverter();
        $input = ['already' => 'array'];

        $result = $converter->convert($input);

        $this->assertEquals($input, $result);
    }

    public function testConvertXmlWithCDATA(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<root><description><![CDATA[<b>Bold text</b> & special chars]]></description></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('description', $result);
    }

    public function testConvertXmlWithNamespace(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<root xmlns:ns="http://example.com"><ns:item>Value</ns:item></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
    }

    public function testConvertXmlDeclarationIsHandled(): void
    {
        $converter = new XmlResponseConverter();
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><name>Test</name></root>';

        $result = $converter->convert($xml);

        $this->assertIsArray($result);
        $this->assertEquals('Test', $result['name']);
    }
}
