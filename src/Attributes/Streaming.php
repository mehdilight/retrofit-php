<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

/**
 * Indicates that this endpoint returns a streaming response.
 * The response body will be returned as a StreamInterface instead of being converted.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Streaming
{
}
