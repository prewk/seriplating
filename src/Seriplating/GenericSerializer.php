<?php

namespace Prewk\Seriplating;

use Illuminate\Support\Arr;
use Prewk\Seriplating\Contracts\IdFactoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;
use Prewk\Seriplating\Errors\IntegrityException;

/**
 * A generic serializer implementation
 */
class GenericSerializer implements SerializerInterface
{
    /**
     * @var IdFactoryInterface
     */
    protected $idFactory;

    /**
     * @var array
     */
    protected $toSerialize;

    /**
     * @param IdFactoryInterface $idFactory
     */
    public function __construct(
        IdFactoryInterface $idFactory
    )
    {
        $this->idFactory = $idFactory;
    }

    /**
     * Serialize according to the rules set up in the template into an array
     *
     * @param array $template Seriplater template
     * @param array $toSerialize Raw data to serialize
     * @return array Serialized data
     */
    public function serialize(array $template, array $toSerialize)
    {
        $this->toSerialize = $toSerialize;

        $serialized = $this->walkUnserializedData($template, $toSerialize);

        return $serialized;
    }

    /**
     * Merge strings with a dot
     *
     * @param $path1 String 1
     * @param $path2 String 2
     * @return string Resulting string
     */
    protected function mergeDotPaths($path1, $path2)
    {
        if ($path1 === "" || $path2 === "") {
            return $path2 . $path1;
        } else {
            return "$path1.$path2";
        }
    }

    /**
     * Go through the unserialized data recursively
     *
     * @param mixed $template Seriplater template/rule
     * @param mixed $data Scope data
     * @param string $dotPath Dot path to scope
     * @return array Serialized results
     * @throws IntegrityException when illegal structures or missing pieces are encountered
     */
    private function walkUnserializedData($template, $data, $dotPath = "")
    {
        if (is_array($template)) {
            $serialized = [];

            foreach ($template as $field => $content) {
                if (
                    $content instanceof RuleInterface &&
                    (
                        (!isset($data[$field]) && $content->isOptional()) ||
                        $content->isHasMany() ||
                        $content->isInherited()
                    )
                ) {
                    continue;
                } elseif (
                    $content instanceof RuleInterface &&
                    $content->isId()
                ) {
                    $serialized["_id"] = $this->idFactory->get($content->getValue(), $data[$field]);
                    continue;
                } elseif (!isset($data[$field])) {
                    throw new IntegrityException("Required field '$field' missing");
                }

                $fieldValue = $this->walkUnserializedData($content, $data[$field], $this->mergeDotPaths($dotPath, $field));
                if (!is_null($fieldValue)) {
                    $serialized[$field] = $fieldValue;
                }
            }

            return $serialized;
        } else {
            if (is_scalar($template)) {
                return $template;
            } elseif ($template->isValue()) {
                return $data;
            } elseif (
                $template->isInherited() ||
                $template->isIncrementing() ||
                $template->isHasMany()
            ) {
                return null;
            } elseif ($template->isReference()) {
                if ($template->isCollection()) {
                    $refs = [];

                    foreach ($data as $dbId) {
                        $refs[] = ["_ref" => $this->idFactory->get($template->getValue()["entityName"], $dbId)];
                    }

                    return $refs;
                } else {
                    return ["_ref" => $this->idFactory->get($template->getValue()["entityName"], $data)];
                }
            } elseif ($template->isConditions()) {
                $value = $template->getValue();
                $field = $value["field"];
                $cases = $value["cases"];
                $defaultCase = $value["defaultCase"];

                if (!isset($this->toSerialize[$field])) {
                    throw new IntegrityException("Required conditions field '$field' missing'");
                }

                foreach ($cases as $case => $rule) {
                    if ($this->toSerialize[$field] == $case) {
                        return $this->walkUnserializedData($rule, $data, $dotPath);
                    }
                }

                if (is_null($defaultCase)) {
                    throw new IntegrityException("No conditions matched, and no default case provided");
                } else {
                    return $this->walkUnserializedData($rule, $data, $dotPath);
                }
            } elseif ($template->isDeep()) {
                $finders = $template->getValue();
                $newData = $data;
                $dotData = Arr::dot($data);

                foreach ($finders as $pattern => $rule) {
                    foreach ($dotData as $innerDotPath => $innerDotValue) {
                        if (preg_match($pattern, $innerDotPath) === 1) {
                            Arr::set($newData, $innerDotPath, $this->walkUnserializedData($rule, $innerDotValue, $this->mergeDotPaths($dotPath, $innerDotPath)));
                        }
                    }
                }

                return $newData;
            } else {
                throw new IntegrityException("Invalid template rule");
            }
        }
    }

}