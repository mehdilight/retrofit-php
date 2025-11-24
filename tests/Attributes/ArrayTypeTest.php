<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\ArrayType;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\Post;
use ReflectionClass;

class ArrayTypeTest extends TestCase
{
    public function testArrayTypeAttribute(): void
    {
        $attr = new ArrayType(Post::class);
        $this->assertSame(Post::class, $attr->type);
    }

    public function testArrayTypeOnProperty(): void
    {
        $class = new class {
            #[ArrayType(Post::class)]
            public array $posts = [];

            public array $tags = [];
        };

        $reflection = new ReflectionClass($class);

        // Check posts property (has ArrayType)
        $postsProp = $reflection->getProperty('posts');
        $attrs = $postsProp->getAttributes(ArrayType::class);
        $this->assertCount(1, $attrs);
        $this->assertSame(Post::class, $attrs[0]->newInstance()->type);

        // Check tags property (no ArrayType)
        $tagsProp = $reflection->getProperty('tags');
        $attrs = $tagsProp->getAttributes(ArrayType::class);
        $this->assertCount(0, $attrs);
    }
}
