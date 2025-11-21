<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class OPTIONS extends HttpMethod
{
    public function method(): string
    {
        return 'OPTIONS';
    }
}
