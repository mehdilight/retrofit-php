<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

final class LinearBackoff implements BackoffStrategy
{
    public function __construct(
        private readonly int $initialDelayMs = 1000,
        private readonly int $incrementMs = 1000,
        private readonly ?int $maxDelayMs = null,
    ) {}

    public function getDelayMs(int $attemptNumber): int
    {
        $delay = $this->initialDelayMs + ($this->incrementMs * $attemptNumber);

        // Apply max delay cap if set
        if ($this->maxDelayMs !== null) {
            $delay = min($delay, $this->maxDelayMs);
        }

        return $delay;
    }
}
