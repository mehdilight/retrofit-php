<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

final class FixedBackoff implements BackoffStrategy
{
    public function __construct(
        private readonly int $delayMs = 1000,
    ) {}

    public function getDelayMs(int $attemptNumber): int
    {
        return $this->delayMs;
    }
}
