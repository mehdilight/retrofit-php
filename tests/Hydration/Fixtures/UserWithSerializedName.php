<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures;

use Phpmystic\RetrofitPhp\Attributes\SerializedName;

class UserWithSerializedName
{
    public int $id;

    #[SerializedName('user_name')]
    public string $name;

    #[SerializedName('email_address')]
    public string $email;

    #[SerializedName('created_at')]
    public ?string $createdAt = null;
}
