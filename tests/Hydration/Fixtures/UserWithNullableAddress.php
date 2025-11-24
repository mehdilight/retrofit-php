<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures;

class UserWithNullableAddress
{
    public int $id;
    public string $name;
    public ?Address $address = null;
}
