<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use ReflectionClass;
use ReflectionProperty;

final class TypedJsonRequestConverter implements Converter
{
    public function convert(mixed $value): string
    {
        if (is_object($value) && !$value instanceof \stdClass) {
            $value = $this->objectToArray($value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convert an object to an array, respecting SerializedName attributes.
     *
     * @return array<string, mixed>
     */
    private function objectToArray(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $result = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $this->getSerializedName($property);
            $value = $property->getValue($object);

            if (is_object($value) && !$value instanceof \stdClass) {
                $value = $this->objectToArray($value);
            } elseif (is_array($value)) {
                $value = $this->convertArrayValues($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Get the JSON key for a property.
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
     * Convert array values recursively.
     *
     * @return array<mixed>
     */
    private function convertArrayValues(array $array): array
    {
        return array_map(function ($item) {
            if (is_object($item) && !$item instanceof \stdClass) {
                return $this->objectToArray($item);
            }
            if (is_array($item)) {
                return $this->convertArrayValues($item);
            }
            return $item;
        }, $array);
    }
}
