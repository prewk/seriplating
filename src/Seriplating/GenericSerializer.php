<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\IdFactoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;
use Prewk\Seriplating\Errors\IntegrityException;

class GenericSerializer implements SerializerInterface
{
    /**
     * @var IdFactoryInterface
     */
    protected $idFactory;

    /**
     * @param IdFactoryInterface $idFactory
     */
    public function __construct(
        IdFactoryInterface $idFactory
    )
    {
        $this->idFactory = $idFactory;
    }

    public function serialize(array $template, array $toSerialize)
    {
        $serialized = $this->walkUnserializedData($template, $toSerialize);

        return $serialized;
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
            $serialized = [];

            foreach ($template as $field => $content) {
                if (!isset($data[$field]) &&
                    $content instanceof RuleInterface &&
                    $content->isOptional()) {
                    continue;
                } elseif (!isset($data[$field])) {
                    throw new IntegrityException("Required field '$field' missing");
                }

                $serialized[$field] = $this->walkUnserializedData($content, $data[$field], $this->mergeDotPaths($dotPath, $field));
            }

            return $serialized;
        } else {
            if ($template->isValue()) {
                return $data;
            } elseif ($template->isReference()) {
                return ["_ref" => $this->idFactory->get($template->getValue(), $data)];
            } else {
                throw new IntegrityException("Invalid template rule");
            }
        }
    }

}