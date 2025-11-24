<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;
use Symfony\Component\Serializer\SerializerInterface;

final class SymfonySerializerStringConverter implements Converter
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $format = 'json',
        private readonly array $context = [],
    ) {}

    public function convert(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        // For arrays and objects, serialize them
        return $this->serializer->serialize($value, $this->format, $this->context);
    }
}
