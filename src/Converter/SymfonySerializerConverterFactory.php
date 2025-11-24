<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use ReflectionType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Symfony\Component\Serializer\SerializerInterface;

final class SymfonySerializerConverterFactory implements ConverterFactory
{
    private SymfonySerializerRequestConverter $requestConverter;
    private SymfonySerializerResponseConverter $responseConverter;
    private SymfonySerializerStringConverter $stringConverter;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        SerializerInterface $serializer,
        string $format = 'json',
        array $context = [],
    ) {
        $this->requestConverter = new SymfonySerializerRequestConverter($serializer, $format, $context);
        $this->responseConverter = new SymfonySerializerResponseConverter($serializer, null, $format, $context);
        $this->stringConverter = new SymfonySerializerStringConverter($serializer, $format, $context);
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
