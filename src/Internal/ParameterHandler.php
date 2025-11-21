<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

final class ParameterHandler
{
    public function __construct(
        public readonly ParameterType $type,
        public readonly string $name,
        public readonly bool $encoded = false,
        public readonly ?string $contentType = null,
    ) {}
}
