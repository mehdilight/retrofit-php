<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use Phpmystic\RetrofitPhp\Http\Response;

/**
 * @template T
 */
interface Call
{
    /**
     * Synchronously execute the request and return its response.
     *
     * @return Response<T>
     */
    public function execute(): Response;

    /**
     * Returns true if this call has been executed.
     */
    public function isExecuted(): bool;

    /**
     * Cancel the request.
     */
    public function cancel(): void;

    /**
     * Returns true if the call has been canceled.
     */
    public function isCanceled(): bool;

    /**
     * Create a new, identical call to this one which can be enqueued or executed.
     *
     * @return Call<T>
     */
    public function clone(): Call;

    /**
     * Returns the original request.
     */
    public function request(): \Phpmystic\RetrofitPhp\Http\Request;
}
