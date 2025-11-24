<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class ResponseType
{
    /**
     * @param class-string $type The class to hydrate to
     * @param bool $isArray Whether the response is an array of this type
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $isArray = false,
    ) {}
}
