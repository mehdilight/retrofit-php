<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use ReflectionNamedType;

final class TypedJsonResponseConverter implements Converter
{
    public function __construct(
        private readonly ObjectHydrator $hydrator,
        private readonly ?ReflectionNamedType $returnType = null,
        private readonly ?ResponseType $responseType = null,
    ) {}

    public function convert(mixed $value): mixed
    {
        // If not a string, return as-is
        if (!is_string($value)) {
            return $value;
        }

        // Empty string returns null
        if ($value === '') {
            return null;
        }

        // Decode JSON
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // If ResponseType attribute is specified, use it for hydration
        if ($this->responseType !== null) {
            return $this->hydrateWithResponseType($decoded);
        }

        // If no return type specified or it's a builtin type, return decoded array
        if ($this->returnType === null || $this->returnType->isBuiltin()) {
            return $decoded;
        }

        $typeName = $this->returnType->getName();

        // Check if the type is a class
        if (!class_exists($typeName)) {
            return $decoded;
        }

        // Hydrate to the target class
        return $this->hydrator->hydrate($decoded, $typeName);
    }

    private function hydrateWithResponseType(mixed $decoded): mixed
    {
        $type = $this->responseType->type;

        if (!class_exists($type)) {
            return $decoded;
        }

        // If isArray is true, hydrate each item in the array
        if ($this->responseType->isArray) {
            if (!is_array($decoded)) {
                return $decoded;
            }

            return array_map(
                fn($item) => $this->hydrator->hydrate($item, $type),
                $decoded
            );
        }

        // Single object hydration
        return $this->hydrator->hydrate($decoded, $type);
    }
}
