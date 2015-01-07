<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\SerializerInterface;
use Prewk\Seriplating\Errors\IntegrityException;

class GenericSerializer implements SerializerInterface
{
    public function serialize(array $template, array $toSerialize)
    {
        return $this->walkUnserializedData($template, $toSerialize);
    }

    protected function mergeDotPaths($path1, $path2)
    {
        if ($path1 === "") {
            return $path2;
        } elseif ($path2 === "") {
            return $path1;
        } else {
            return "$path1.$path2";
        }
    }

    private function walkUnserializedData($template, $data, $dotPath = "")
    {
        if (is_array($template)) {
            foreach ($template as $field => $content) {
                if (!isset($data[$field]) &&
                    $content instanceof RuleInterface &&
                    $content->isOptional()) {
                    break;
                } elseif (!isset($data[$field])) {
                    throw new IntegrityException("Required field '$field' missing");
                }

                $this->walkUnserializedData($content, $data[$field], $this->mergeDotPaths($dotPath, $field));
            }
        } else {
            if ($data->isValue()) {
                return $data;
            } else {
                throw new IntegrityException("Invalid template rule");
            }
        }
    }

}