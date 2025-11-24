<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;

final class XmlResponseConverter implements Converter
{
    public function convert(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        // Suppress XML parsing errors
        $useErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($value, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                libxml_clear_errors();
                return null;
            }

            // Convert SimpleXMLElement to array
            $result = $this->xmlToArray($xml);

            return $result;
        } finally {
            libxml_use_internal_errors($useErrors);
        }
    }

    private function xmlToArray(\SimpleXMLElement $xml): mixed
    {
        $array = [];

        // Get attributes
        foreach ($xml->attributes() as $name => $value) {
            $array['@' . $name] = (string) $value;
        }

        // Get child elements
        $children = $xml->children();

        if (count($children) === 0) {
            // No children, get text content
            $text = trim((string) $xml);
            if ($text !== '') {
                if (empty($array)) {
                    // Return string directly if no attributes
                    return $text;
                }
                $array['_value'] = $text;
            }
            return $array ?: [];
        }

        // Group children by name
        $grouped = [];
        foreach ($children as $name => $child) {
            $grouped[(string) $name][] = $child;
        }

        // Convert each group
        foreach ($grouped as $name => $elements) {
            if (count($elements) === 1) {
                // Single element
                $childArray = $this->xmlToArray($elements[0]);
                $array[$name] = $childArray;
            } else {
                // Multiple elements with same name - create array
                $array[$name] = [];
                foreach ($elements as $element) {
                    $array[$name][] = $this->xmlToArray($element);
                }
            }
        }

        return $array;
    }
}
