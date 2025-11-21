<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use ReflectionType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

final class JsonConverterFactory implements ConverterFactory
{
    private JsonRequestConverter $requestConverter;
    private JsonResponseConverter $responseConverter;
    private JsonStringConverter $stringConverter;

    public function __construct()
    {
        $this->requestConverter = new JsonRequestConverter();
        $this->responseConverter = new JsonResponseConverter();
        $this->stringConverter = new JsonStringConverter();
    }

    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return $this->requestConverter;
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        return $this->responseConverter;
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return $this->stringConverter;
    }
}
