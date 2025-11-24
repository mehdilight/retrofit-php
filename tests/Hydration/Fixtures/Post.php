<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures;

use Phpmystic\RetrofitPhp\Attributes\SerializedName;

class Post
{
    public int $id;
    public string $title;
    public string $body;

    #[SerializedName('user_id')]
    public int $userId;
}
