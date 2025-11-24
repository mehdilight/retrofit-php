<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures;

use Phpmystic\RetrofitPhp\Attributes\ArrayType;

class UserWithPosts
{
    public int $id;
    public string $name;

    #[ArrayType(Post::class)]
    public array $posts = [];
}
