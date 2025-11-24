<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

interface Chain
{
    /**
     * Get the current request in the chain.
     */
    public function request(): Request;

    /**
     * Proceed with the (potentially modified) request.
     */
    public function proceed(Request $request): Response;
}
