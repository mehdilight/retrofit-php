<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

final class ExponentialBackoff implements BackoffStrategy
{
    public function __construct(
        private readonly int $baseDelayMs = 1000,
        private readonly float $multiplier = 2.0,
        private readonly ?int $maxDelayMs = null,
        private readonly bool $jitter = false,
    ) {}

    public function getDelayMs(int $attemptNumber): int
    {
        $delay = $this->baseDelayMs * pow($this->multiplier, $attemptNumber);

        // Apply max delay cap if set
        if ($this->maxDelayMs !== null) {
            $delay = min($delay, $this->maxDelayMs);
        }

        // Apply jitter (randomize delay between 0 and calculated delay)
        if ($this->jitter) {
            $delay = mt_rand(0, (int) $delay);
        }

        return (int) $delay;
    }
}
