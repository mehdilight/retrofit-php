<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use ReflectionType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

final class XmlConverterFactory implements ConverterFactory
{
    private XmlRequestConverter $requestConverter;
    private XmlResponseConverter $responseConverter;
    private XmlStringConverter $stringConverter;

    public function __construct(
        string $rootElement = 'root',
        string $version = '1.0',
        string $encoding = 'UTF-8',
    ) {
        $this->requestConverter = new XmlRequestConverter($rootElement, $version, $encoding);
        $this->responseConverter = new XmlResponseConverter();
        $this->stringConverter = new XmlStringConverter();
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
