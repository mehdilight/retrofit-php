<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use ReflectionType;
use ReflectionNamedType;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ResponseTypeAwareConverterFactory;

final class TypedJsonConverterFactory implements ResponseTypeAwareConverterFactory
{
    private ObjectHydrator $hydrator;
    private TypedJsonRequestConverter $requestConverter;
    private JsonStringConverter $stringConverter;

    public function __construct()
    {
        $this->hydrator = new ObjectHydrator();
        $this->requestConverter = new TypedJsonRequestConverter();
        $this->stringConverter = new JsonStringConverter();
    }

    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return $this->requestConverter;
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        $namedType = $type instanceof ReflectionNamedType ? $type : null;
        return new TypedJsonResponseConverter($this->hydrator, $namedType);
    }

    public function responseBodyConverterWithResponseType(
        ?ReflectionType $type,
        ?ResponseType $responseType,
    ): ?Converter {
        $namedType = $type instanceof ReflectionNamedType ? $type : null;
        return new TypedJsonResponseConverter($this->hydrator, $namedType, $responseType);
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return $this->stringConverter;
    }
}
