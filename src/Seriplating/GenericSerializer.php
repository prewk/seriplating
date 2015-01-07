<?php

namespace Prewk\Seriplating;

use Illuminate\Support\Arr;
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

    public function serialize(array $template, array $toSerialize)
    {
        $this->toSerialize = $toSerialize;

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
                if (
                    !isset($data[$field]) &&
                    $content instanceof RuleInterface &&
                    $content->isOptional()
                ) {
                    continue;
                } elseif (!isset($data[$field])) {
                    throw new IntegrityException("Required field '$field' missing");
                } elseif (
                    $content instanceof RuleInterface &&
                    $content->isId()
                ) {
                    $serialized["_id"] = $this->idFactory->get($content->getValue(), $data[$field]);
                } else {
                    $serialized[$field] = $this->walkUnserializedData($content, $data[$field], $this->mergeDotPaths($dotPath, $field));

                }
            }

            return $serialized;
        } else {
            if ($template->isValue()) {
                return $data;
            } elseif ($template->isReference()) {
                if ($template->isCollection()) {
                    $refs = [];

                    foreach ($data as $dbId) {
                        $refs[] = ["_ref" => $this->idFactory->get($template->getValue(), $dbId)];
                    }

                    return $refs;
                } else {
                    return ["_ref" => $this->idFactory->get($template->getValue(), $data)];
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