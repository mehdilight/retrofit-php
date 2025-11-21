<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\HEAD;
use Phpmystic\RetrofitPhp\Attributes\Http\OPTIONS;
use Phpmystic\RetrofitPhp\Attributes\Http\PATCH;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;

class HttpMethodAttributeTest extends TestCase
{
    public function testGetAttribute(): void
    {
        $attr = new GET('/users');
        $this->assertSame('GET', $attr->method());
        $this->assertSame('/users', $attr->path);
    }

    public function testPostAttribute(): void
    {
        $attr = new POST('/users');
        $this->assertSame('POST', $attr->method());
        $this->assertSame('/users', $attr->path);
    }

    public function testPutAttribute(): void
    {
        $attr = new PUT('/users/{id}');
        $this->assertSame('PUT', $attr->method());
        $this->assertSame('/users/{id}', $attr->path);
    }

    public function testDeleteAttribute(): void
    {
        $attr = new DELETE('/users/{id}');
        $this->assertSame('DELETE', $attr->method());
        $this->assertSame('/users/{id}', $attr->path);
    }

    public function testPatchAttribute(): void
    {
        $attr = new PATCH('/users/{id}');
        $this->assertSame('PATCH', $attr->method());
        $this->assertSame('/users/{id}', $attr->path);
    }

    public function testHeadAttribute(): void
    {
        $attr = new HEAD('/users');
        $this->assertSame('HEAD', $attr->method());
        $this->assertSame('/users', $attr->path);
    }

    public function testOptionsAttribute(): void
    {
        $attr = new OPTIONS('/users');
        $this->assertSame('OPTIONS', $attr->method());
        $this->assertSame('/users', $attr->path);
    }

    public function testEmptyPath(): void
    {
        $attr = new GET();
        $this->assertSame('', $attr->path);
    }
}
