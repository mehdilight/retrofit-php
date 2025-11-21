<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use ReflectionMethod;

interface CallAdapterFactory
{
    /**
     * Returns a CallAdapter for the return type of the given method, or null if this factory
     * cannot handle the type.
     *
     * @return CallAdapter<mixed>|null
     */
    public function get(ReflectionMethod $method): ?CallAdapter;
}
