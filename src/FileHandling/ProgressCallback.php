<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\FileHandling;

final class ProgressCallback
{
    /**
     * @param callable(int, int): void $callback
     */
    public function __construct(
        private $callback
    ) {}

    /**
     * Invoke the progress callback.
     *
     * @param int $bytesTransferred Number of bytes transferred so far
     * @param int $totalBytes Total number of bytes (0 if unknown)
     */
    public function __invoke(int $bytesTransferred, int $totalBytes): void
    {
        ($this->callback)($bytesTransferred, $totalBytes);
    }

    /**
     * Get the underlying callback.
     *
     * @return callable(int, int): void
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }
}
