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
     * @param array $serialized Serialization
     * @return string Formatted serialization
     */
    public function formatSerialized(array $serialized)
    {
        $xml = new SimpleXMLElement("<root />");
        $this->formatTree($xml, $serialized, $xml);

        return $xml->asXML();
    }

    private function formatTree(SimpleXMLElement $xml, array $serialized, SimpleXMLElement $rootNode = null, $parentName = null, SimpleXMLElement $parentNode = null)
    {
        foreach ($serialized as $key => $value) {
            if (is_array($value)) {
                // Branch
                if (count($value) === 0) {
                    // Empty array
                    $child = $xml->addChild($key);
                    $child->addAttribute("array", null);
                } else {
                    if (is_numeric($key)) {
                        // Numeric in array, singularize
                        $key = Pluralizer::singular($parentName);
                        if (!isset($parentNode["array"])) {
                            $parentNode->addAttribute("array", $key);
                        }
                    }
                    $child = $xml->addChild($key);
                    $this->formatTree($child, $value, $rootNode, $key, $child);
                }
            } else {
                // Leaf
                if ($key === "_ref") {
                    // Ref
                    $parentNode->addAttribute("ref", $value);
                } elseif ($key === "_id") {
                    // Id
                    if (!is_null($parentNode)) {
                        $parentNode->addAttribute("id", $value);
                    } else {
                        $rootNode->addAttribute("id", $value);
                    }
                } elseif (is_null($value)) {
                    // If value is null, denote it in the attribute
                    $child = $xml->addChild($key);
                    $child->addAttribute("scalar", "null");
                } elseif (is_int($value)) {
                    // If value is int, denote it in the attribute
                    $child = $xml->addChild($key, $value);
                    $child->addAttribute("scalar", "integer");
                } elseif (is_float($value)) {
                    // If value is float, denote it in the attribute
                    $child = $xml->addChild($key, $value);
                    $child->addAttribute("scalar", "float");
                } elseif (is_bool($value)) {
                    // If value is boolean, denote it in the attribute
                    $child = $xml->addChild($key, $value ? 1 : 0);
                    $child->addAttribute("scalar", "boolean");
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
                if (!is_null($attributes["ref"])) {
                    // Special case: ref
                    $tree[$key] = ["_ref" => (string)$attributes["ref"]];
                } elseif (!is_null($attributes["scalar"])) {
                    $scalarAttr = (string)$attributes["scalar"];
                    switch ($scalarAttr) {
                        case "null":
                            // Scalar null
                            $tree[$key] = null;
                            break;
                        case "integer":
                            // Scalar integer
                            $tree[$key] = (int)$value;
                            break;
                        case "float":
                            // Scalar float
                            $tree[$key] = (float)$value;
                            break;
                        case "boolean":
                            // Scalar boolean
                            $tree[$key] = (string)$value === "1";
                            break;
                    }
                } elseif (!is_null($attributes["array"])) {
                    // Empty array
                    $tree[$key] = [];
                } else {
                    // Normal string value
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