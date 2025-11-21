<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Header;
use Phpmystic\RetrofitPhp\Attributes\HeaderMap;
use Phpmystic\RetrofitPhp\Attributes\Headers;

class HeaderAttributeTest extends TestCase
{
    public function testHeaderAttribute(): void
    {
        $attr = new Header('Authorization');
        $this->assertSame('Authorization', $attr->name);
    }

    public function testHeaderMapAttribute(): void
    {
        $attr = new HeaderMap();
        $this->assertInstanceOf(HeaderMap::class, $attr);
    }

    public function testHeadersAttribute(): void
    {
        $attr = new Headers(
            'Content-Type: application/json',
            'Accept: application/json',
        );
        $this->assertSame([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $attr->headers);
    }

    public function testHeadersAttributeWithMalformedHeader(): void
    {
        $attr = new Headers(
            'Content-Type: application/json',
            'InvalidHeader',
            'Accept: application/json',
        );
        $this->assertSame([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $attr->headers);
    }
}
