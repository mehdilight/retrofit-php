<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use Phpmystic\RetrofitPhp\Http\Response;

interface Interceptor
{
    /**
     * Intercept the request/response chain.
     *
     * Implementations can:
     * - Modify the request before proceeding
     * - Short-circuit and return a response without calling proceed
     * - Modify the response after proceed returns
     */
    public function intercept(Chain $chain): Response;
}
