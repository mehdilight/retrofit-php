<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;
use Phpmystic\RetrofitPhp\Attributes\Parameter\FieldMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;
use Phpmystic\RetrofitPhp\Attributes\Parameter\PartMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\QueryMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Url;

class ParameterAttributeTest extends TestCase
{
    public function testPathAttribute(): void
    {
        $attr = new Path('userId');
        $this->assertSame('userId', $attr->name);
        $this->assertFalse($attr->encoded);
    }

    public function testPathAttributeEncoded(): void
    {
        $attr = new Path('userId', encoded: true);
        $this->assertTrue($attr->encoded);
    }

    public function testQueryAttribute(): void
    {
        $attr = new Query('page');
        $this->assertSame('page', $attr->name);
        $this->assertFalse($attr->encoded);
    }

    public function testQueryMapAttribute(): void
    {
        $attr = new QueryMap();
        $this->assertFalse($attr->encoded);
    }

    public function testBodyAttribute(): void
    {
        $attr = new Body();
        $this->assertInstanceOf(Body::class, $attr);
    }

    public function testFieldAttribute(): void
    {
        $attr = new Field('username');
        $this->assertSame('username', $attr->name);
        $this->assertFalse($attr->encoded);
    }

    public function testFieldMapAttribute(): void
    {
        $attr = new FieldMap(encoded: true);
        $this->assertTrue($attr->encoded);
    }

    public function testPartAttribute(): void
    {
        $attr = new Part('file', 'image/png');
        $this->assertSame('file', $attr->name);
        $this->assertSame('image/png', $attr->contentType);
    }

    public function testPartMapAttribute(): void
    {
        $attr = new PartMap('application/json');
        $this->assertSame('application/json', $attr->contentType);
    }

    public function testUrlAttribute(): void
    {
        $attr = new Url();
        $this->assertInstanceOf(Url::class, $attr);
    }
}
