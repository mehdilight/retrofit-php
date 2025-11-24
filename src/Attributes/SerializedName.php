<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class SerializedName
{
    public function __construct(
        public readonly string $name,
    ) {}
}
