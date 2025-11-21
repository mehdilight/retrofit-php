<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Header
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}
}
