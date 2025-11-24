<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayType
{
    /**
     * @param class-string $type
     */
    public function __construct(
        public readonly string $type,
    ) {}
}
