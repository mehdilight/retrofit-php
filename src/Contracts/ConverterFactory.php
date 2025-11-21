<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use ReflectionType;

interface ConverterFactory
{
    /**
     * Returns a Converter for converting request body to the HTTP request body.
     */
    public function requestBodyConverter(?ReflectionType $type): ?Converter;

    /**
     * Returns a Converter for converting the HTTP response body to the specified type.
     */
    public function responseBodyConverter(?ReflectionType $type): ?Converter;

    /**
     * Returns a Converter for converting a value to a string (for path, query, header, etc.)
     */
    public function stringConverter(?ReflectionType $type): ?Converter;
}
