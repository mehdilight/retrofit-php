<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Converter;

use Phpmystic\RetrofitPhp\Contracts\Converter;

final class XmlStringConverter implements Converter
{
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

        // For arrays and objects, convert to XML
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $xml->addChild((string) $key, htmlspecialchars((string) $val, ENT_XML1, 'UTF-8'));
            }
        } elseif (is_object($value)) {
            foreach (get_object_vars($value) as $key => $val) {
                $xml->addChild($key, htmlspecialchars((string) $val, ENT_XML1, 'UTF-8'));
            }
        }

        return $xml->asXML();
    }
}
