<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Headers
{
    /** @var array<string, string> */
    public readonly array $headers;

    public function __construct(string ...$headers)
    {
        $parsed = [];
        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            }
        }
        $this->headers = $parsed;
    }
}
