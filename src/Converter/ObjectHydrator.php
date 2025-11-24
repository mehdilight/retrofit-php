<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Attributes\ArrayType;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

final class ObjectHydrator
{
    /**
     * Hydrate an array of data into an object of the given class.
     *
     * @template T of object
     * @param array<string, mixed>|null $data
     * @param class-string<T> $className
     * @return T|null
     */
    public function hydrate(?array $data, string $className): ?object
    {
        if ($data === null) {
            return null;
        }

        $reflection = new ReflectionClass($className);
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $this->getSerializedName($property);

            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $value = $this->convertValue($value, $property);

            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * Hydrate an array of arrays into an array of objects.
     *
     * @template T of object
     * @param array<array<string, mixed>>|null $data
     * @param class-string<T> $className
     * @return array<T>
     */
    public function hydrateArray(?array $data, string $className): array
    {
        if ($data === null) {
            return [];
        }

        return array_map(
            fn(array $item) => $this->hydrate($item, $className),
            $data
        );
    }

    /**
     * Get the JSON key for a property (either from SerializedName or property name).
     */
    private function getSerializedName(ReflectionProperty $property): string
    {
        $attributes = $property->getAttributes(SerializedName::class);

        if (!empty($attributes)) {
            return $attributes[0]->newInstance()->name;
        }

        return $property->getName();
    }

    /**
     * Convert a value based on the property type.
     */
    private function convertValue(mixed $value, ReflectionProperty $property): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $property->getType();

        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Check for ArrayType attribute (array of objects)
        if ($typeName === 'array' && is_array($value)) {
            $arrayTypeAttrs = $property->getAttributes(ArrayType::class);

            if (!empty($arrayTypeAttrs)) {
                $itemClass = $arrayTypeAttrs[0]->newInstance()->type;
                return $this->hydrateArray($value, $itemClass);
            }

            return $value;
        }

        // Check if type is a class (nested object)
        if (!$type->isBuiltin() && is_array($value) && class_exists($typeName)) {
            return $this->hydrate($value, $typeName);
        }

        return $value;
    }
}
