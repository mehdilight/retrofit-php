<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;

final class XmlRequestConverter implements Converter
{
    public function __construct(
        private readonly string $rootElement = 'root',
        private readonly string $version = '1.0',
        private readonly string $encoding = 'UTF-8',
    ) {}

    public function convert(mixed $value): string
    {
        // Convert objects to arrays
        if (is_object($value)) {
            $value = $this->objectToArray($value);
        }

        // Create XML
        $xml = new \SimpleXMLElement(
            "<?xml version=\"{$this->version}\" encoding=\"{$this->encoding}\"?><{$this->rootElement}/>"
        );

        if (is_array($value)) {
            $this->arrayToXml($value, $xml);
        } else {
            $xml[0] = (string) $value;
        }

        return $xml->asXML();
    }

    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                // For numeric keys, use 'item' as element name
                $key = 'item';
            }

            if (is_array($value)) {
                // Check if it's a list of values (all numeric keys)
                if ($this->isSequentialArray($value)) {
                    // Create parent wrapper element
                    $parentElement = $xml->addChild($key);

                    // Singularize the key for child elements
                    $childKey = $this->singularize($key);

                    foreach ($value as $item) {
                        $child = $parentElement->addChild($childKey);
                        if (is_array($item)) {
                            $this->arrayToXml($item, $child);
                        } else {
                            $child[0] = htmlspecialchars((string) $item, ENT_XML1, 'UTF-8');
                        }
                    }
                } else {
                    $child = $xml->addChild($key);
                    $this->arrayToXml($value, $child);
                }
            } elseif (is_object($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXml($this->objectToArray($value), $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
            }
        }
    }

    private function singularize(string $word): string
    {
        // Simple singularization - just remove trailing 's' if present
        // This is a basic implementation - a real one would use more rules
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        if (str_ends_with($word, 'es')) {
            return substr($word, 0, -2);
        }
        if (str_ends_with($word, 's') && strlen($word) > 1) {
            return substr($word, 0, -1);
        }
        return $word;
    }

    private function objectToArray(object $obj): array
    {
        if (method_exists($obj, 'toArray')) {
            return $obj->toArray();
        }

        $array = [];
        foreach (get_object_vars($obj) as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $this->objectToArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    private function isSequentialArray(array $arr): bool
    {
        if (empty($arr)) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
