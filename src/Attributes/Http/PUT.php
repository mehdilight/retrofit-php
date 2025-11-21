<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class PUT extends HttpMethod
{
    public function method(): string
    {
        return 'PUT';
    }
}
