<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;
use Symfony\Component\Serializer\SerializerInterface;

final class SymfonySerializerRequestConverter implements Converter
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
        return $this->serializer->serialize($value, $this->format, $this->context);
    }
}
