<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes\Parameter;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Part
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly string $contentType = 'text/plain',
    ) {}
}
