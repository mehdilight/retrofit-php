<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

interface BackoffStrategy
{
    /**
     * Get the delay in milliseconds before the next retry attempt.
     *
     * @param int $attemptNumber The zero-based attempt number (0 for first retry)
     * @return int Delay in milliseconds
     */
    public function getDelayMs(int $attemptNumber): int;
}
