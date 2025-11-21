<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use ReflectionType;

/**
 * Adapts a Call into another type.
 *
 * @template T
 */
interface CallAdapter
{
    /**
     * Returns the value type that this adapter uses when converting the response body.
     */
    public function responseType(): ?ReflectionType;

    /**
     * Returns an instance of T which delegates to call.
     *
     * @param Call<mixed> $call
     * @return T
     */
    public function adapt(Call $call): mixed;
}
