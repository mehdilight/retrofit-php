<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Cacheable
{
    public function __construct(
        public readonly int $ttl = 60,
    ) {}
}
