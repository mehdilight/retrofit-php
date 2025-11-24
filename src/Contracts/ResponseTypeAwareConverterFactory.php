<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use ReflectionType;

/**
 * Extension of ConverterFactory that supports ResponseType attribute for array hydration.
 */
interface ResponseTypeAwareConverterFactory extends ConverterFactory
{
    /**
     * Returns a Converter for converting the HTTP response body using ResponseType metadata.
     */
    public function responseBodyConverterWithResponseType(
        ?ReflectionType $type,
        ?ResponseType $responseType,
    ): ?Converter;
}
