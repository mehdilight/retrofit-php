<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;
use Symfony\Component\Serializer\SerializerInterface;

final class SymfonySerializerResponseConverter implements Converter
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ?string $targetType = null,
        private readonly string $format = 'json',
        private readonly array $context = [],
    ) {}

    public function convert(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        try {
            // If target type specified, deserialize to that type
            if ($this->targetType !== null) {
                // Handle array types like "User[]"
                if (str_ends_with($this->targetType, '[]')) {
                    $itemType = substr($this->targetType, 0, -2);
                    return $this->serializer->deserialize(
                        $value,
                        $itemType . '[]',
                        $this->format,
                        $this->context
                    );
                }

                return $this->serializer->deserialize(
                    $value,
                    $this->targetType,
                    $this->format,
                    $this->context
                );
            }

            // Otherwise, decode as array using json_decode or similar
            // Symfony Serializer doesn't support deserializing to plain array without a type
            if ($this->format === 'json') {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }
                return $decoded;
            }

            // For other formats, attempt deserialization
            return $this->serializer->deserialize(
                $value,
                'array',
                $this->format,
                $this->context
            );
        } catch (\Exception $e) {
            // If deserialization fails, return null
            return null;
        }
    }
}
