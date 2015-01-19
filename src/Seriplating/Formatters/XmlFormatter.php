<?php

namespace Prewk\Seriplating\Formatters;

use Illuminate\Support\Pluralizer;
use Prewk\Seriplating\Contracts\FormatterInterface;
use SimpleXMLElement;

/**
 * Format a serialization into XML
 */
class XmlFormatter implements FormatterInterface
{
    /**
     * Format a serialization
     *
     * @param string $serialized Serialization
     * @return string Formatted serialization
     */
    public function formatSerialized($serialized)
    {
        $xml = new SimpleXMLElement("<root />");
        $this->formatTree($xml, $serialized, $xml);

        return $xml->asXML();
    }

    private function formatTree(SimpleXMLElement $xml, $serialized, SimpleXMLElement $rootNode = null, $parentName = null, SimpleXMLElement $parentNode = null)
    {
        foreach ($serialized as $key => $value) {
            if (is_array($value)) {
                // Branch
                if (is_numeric($key)) {
                    // Numeric in array, singularize
                    $key = Pluralizer::singular($parentName);
                    if (!isset($parentNode["array"])) {
                        $parentNode->addAttribute("array", $key);
                    }
                }
                $child = $xml->addChild($key);
                $this->formatTree($child, $value, $rootNode, $key, $child);
            } else {
                // Leaf
                if ($key === "_ref") {
                    // Ref
                    $parentNode->addAttribute("ref", $value);
                } else if ($key === "_id") {
                    // Id
                    if (!is_null($parentNode)) {
                        $parentNode->addAttribute("id", $value);
                    } else {
                        $rootNode->addAttribute("id", $value);
                    }
                } else {
                    // Value
                    $xml->addChild($key, $value);
                }
            }
        }
    }

    /**
     * Convert a formatted serialization
     *
     * @param string $formatted Formatted serialization
     * @return void Serialization
     */
    public function unformatSerialized($formatted)
    {
        $xml = new SimpleXMLElement($formatted);

        $serialization = $this->unformatTree($xml);

        return $serialization;
    }

    private function unformatTree(SimpleXMLElement $xml)
    {
        $tree = [];
        $parentAttributes = $xml->attributes();

        if (isset($parentAttributes["id"])) {
            $tree["_id"] = (string)$parentAttributes["id"];
        }

        foreach ($xml as $key => $value) {
            // $key is child node name
            // $value is child node
            // $children is grandchildren
            // $attributes is child attributes
            $children = $value->children();
            $attributes = $value->attributes();

            if (count($children) === 0) {
                // Return value
                if ($attributes["ref"]) {
                    // Special case: ref
                    $tree[$key] = ["_ref" => (string)$attributes["ref"]];
                } else {
                    // Normal value
                    $tree[$key] = (string)$value;
                }
            } else {
                // Handle children
                if (isset($attributes["array"])) {
                    $tree[$key] = [];
                    foreach ($children as $grandChild) {
                        $tree[$key][] = $this->unformatTree($grandChild);
                    }
                } else {
                    $tree[$key] = $this->unformatTree($value);
                }
            }
        }

        return $tree;
    }
}