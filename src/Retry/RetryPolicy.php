<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Retry;

use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

final class RetryPolicy
{
    /**
     * @param int $maxAttempts Maximum number of attempts (including the initial request)
     * @param array<int> $retryableStatusCodes HTTP status codes that should trigger a retry
     * @param array<class-string<\Throwable>> $retryableExceptions Exception types that should trigger a retry
     * @param BackoffStrategy $backoffStrategy Strategy for calculating delay between retries
     */
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly array $retryableStatusCodes = [429, 500, 502, 503, 504],
        private readonly array $retryableExceptions = [\RuntimeException::class, \Exception::class],
        private readonly BackoffStrategy $backoffStrategy = new ExponentialBackoff(),
    ) {}

    /**
     * Create a builder for constructing a RetryPolicy.
     */
    public static function builder(): RetryPolicyBuilder
    {
        return new RetryPolicyBuilder();
    }

    /**
     * Create a default retry policy with sensible defaults.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create a policy that never retries.
     */
    public static function none(): self
    {
        return new self(maxAttempts: 0);
    }

    /**
     * Determine if a request should be retried.
     *
     * @param Request $request The original request
     * @param Response|null $response The response if one was received
     * @param \Throwable|null $exception The exception if one was thrown
     * @param int $attemptNumber The zero-based attempt number (0 = first retry, 1 = second retry, etc.)
     * @return bool True if the request should be retried
     */
    public function shouldRetry(
        Request $request,
        ?Response $response,
        ?\Throwable $exception,
        int $attemptNumber,
    ): bool {
        // Check if we've exceeded max attempts
        // attemptNumber represents which attempt we just completed (0-indexed)
        // maxAttempts is the total number of attempts allowed
        // If maxAttempts = 3, we allow attempts 0, 1, 2 (3 total)
        // After completing attempt 2, attemptNumber = 2, and we should not retry (2 + 1 = 3 attempts made)
        if ($attemptNumber + 1 >= $this->maxAttempts) {
            return false;
        }

        // Check if response status code is retryable
        if ($response !== null && in_array($response->code, $this->retryableStatusCodes, true)) {
            return true;
        }

        // Check if exception is retryable
        if ($exception !== null) {
            foreach ($this->retryableExceptions as $exceptionClass) {
                if ($exception instanceof $exceptionClass) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the delay in milliseconds before the next retry attempt.
     */
    public function getDelayMs(int $attemptNumber): int
    {
        return $this->backoffStrategy->getDelayMs($attemptNumber);
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getBackoffStrategy(): BackoffStrategy
    {
        return $this->backoffStrategy;
    }
}
