<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\Post;

class ResponseTypeTest extends TestCase
{
    public function testResponseTypeSingleObject(): void
    {
        $attr = new ResponseType(Post::class);
        $this->assertSame(Post::class, $attr->type);
        $this->assertFalse($attr->isArray);
    }

    public function testResponseTypeArray(): void
    {
        $attr = new ResponseType(Post::class, isArray: true);
        $this->assertSame(Post::class, $attr->type);
        $this->assertTrue($attr->isArray);
    }
}
