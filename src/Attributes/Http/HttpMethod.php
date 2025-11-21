<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
abstract class HttpMethod
{
    public function __construct(
        public readonly string $path = '',
    ) {}

    abstract public function method(): string;
}
