<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures;

use Phpmystic\RetrofitPhp\Attributes\ArrayType;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;

class Company
{
    public int $id;

    #[SerializedName('company_name')]
    public string $name;

    public Address $headquarters;

    #[ArrayType(SimpleUser::class)]
    public array $employees = [];
}
