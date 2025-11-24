<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

final class RetryPolicyBuilder
{
    private int $maxAttempts = 3;
    private array $retryableStatusCodes = [429, 500, 502, 503, 504];
    private array $retryableExceptions = [\RuntimeException::class, \Exception::class];
    private BackoffStrategy $backoffStrategy;

    public function __construct()
    {
        $this->backoffStrategy = new ExponentialBackoff();
    }

    /**
     * Set the maximum number of attempts (including the initial request).
     */
    public function maxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * Set the HTTP status codes that should trigger a retry.
     *
     * @param array<int> $statusCodes
     */
    public function retryOnStatusCodes(array $statusCodes): self
    {
        $this->retryableStatusCodes = $statusCodes;
        return $this;
    }

    /**
     * Set the exception types that should trigger a retry.
     *
     * @param array<class-string<\Throwable>> $exceptions
     */
    public function retryOnExceptions(array $exceptions): self
    {
        $this->retryableExceptions = $exceptions;
        return $this;
    }

    /**
     * Set the backoff strategy for calculating delays between retries.
     */
    public function backoffStrategy(BackoffStrategy $strategy): self
    {
        $this->backoffStrategy = $strategy;
        return $this;
    }

    /**
     * Build the RetryPolicy with the configured options.
     */
    public function build(): RetryPolicy
    {
        return new RetryPolicy(
            maxAttempts: $this->maxAttempts,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions,
            backoffStrategy: $this->backoffStrategy,
        );
    }
}
