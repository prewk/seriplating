<?php

namespace Prewk\Seriplating;

use Illuminate\Support\Arr;
use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\IntegrityException;

/**
 * A generic deserializer implementation
 */
class GenericDeserializer implements DeserializerInterface
{
    /**
     * @var IdResolverInterface
     */
    protected $idResolver;

    /**
     * @var array
     */
    protected $toUnserialize;

    /**
     * @var string
     */
    protected $idName;

    /**
     * @var array
     */
    protected $updatesToDefer = [];

    /**
     * @var array
     */
    protected $inherited;

    /**
     * @param IdResolverInterface $idResolver
     */
    public function __construct(
        IdResolverInterface $idResolver
    )
    {
        $this->idResolver = $idResolver;
    }

    /**
     * Deserialize according to the rules set up in the template into the repository
     *
     * @param array $template Seriplater template
     * @param RepositoryInterface $repository Target repository
     * @param array $toDeserialize Serialized data
     * @param array $inherited Inherited data from a parent entity
     * @param string $primaryKeyField Name of primary key field
     * @return array The created entity
     */
    public function deserialize(array $template, RepositoryInterface $repository, array $toDeserialize, array $inherited = [], $primaryKeyField = "id")
    {
        $this->toUnserialize = $toDeserialize;
        $this->inherited = $inherited;

        $entityData = $this->walkDeserializedData($template, $toDeserialize);

        $createdEntity = $repository->create($entityData);
        $primaryKey = $createdEntity[$primaryKeyField];

        if (isset($this->idName)) {
            $this->idResolver->bind($this->idName, $primaryKey);
        }

        foreach ($this->updatesToDefer as $updateToDefer) {
            $this->idResolver->deferResolution($updateToDefer["internalId"], function($dbId) use ($repository, $updateToDefer, $primaryKey, $createdEntity) {
                if ($updateToDefer["overwriteField"]) {
                    $repository->update($primaryKey, $updateToDefer["targetField"], $dbId);
                } else {
                    $newFieldData = $createdEntity[$updateToDefer["targetField"]];
                    Arr::set($newFieldData, $updateToDefer["dotPath"], $dbId);
                    $repository->update($primaryKey, $updateToDefer["targetField"], $newFieldData);
                }
            });
        }

        return $createdEntity;
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

    private function walkDeserializedData($template, $data, $dotPath = "")
    {
        if (is_array($template)) {
            $entityData = [];

            foreach ($template as $field => $content) {
                if (
                    $content instanceof RuleInterface &&
                    $content->isId() &&
                    isset($data["_id"])
                ) {
                    $this->idName = $data["_id"];
                    continue;
                } elseif (
                    $content instanceof RuleInterface &&
                    $content->isHasMany()
                ) {
                    continue;
                } elseif (
                    $content instanceof RuleInterface &&
                    $content->isInherited()
                ) {
                    $fieldToInherit = $content->getValue();
                    if (!isset($this->inherited[$fieldToInherit])) {
                        throw new IntegrityException("Required inherited '$field' wasn't supplied");
                    }

                    $entityData[$field] = $this->inherited[$fieldToInherit];
                    continue;
                } elseif (
                    !isset($data[$field]) &&
                    $content instanceof RuleInterface &&
                    $content->isOptional()
                ) {
                    continue;
                } elseif (!isset($data[$field])) {
                    throw new IntegrityException("Required field '$field' missing");
                } else {
                    $entityData[$field] = $this->walkDeserializedData($content, $data[$field], $this->mergeDotPaths($dotPath, $field));
                }
            }

            return $entityData;
        } else {
            if ($template->isValue()) {
                return $data;
            } elseif ($template->isReference()) {
                $dotParts = explode(".", $dotPath);

                $this->updatesToDefer[] = [
                    "targetField" => $dotParts[0],
                    "entityName" => $template->getValue(),
                    "internalId" => $data["_ref"],
                    "overwriteField" => count($dotParts) === 1,
                    "dotPath" => (count($dotParts) === 1) ? null : substr($dotPath, strlen($dotParts[0] + 1)),
                ];

                return 0;
            } elseif ($template->isConditions()) {
                $value = $template->getValue();
                $field = $value["field"];
                $cases = $value["cases"];
                $defaultCase = $value["defaultCase"];

                if (!isset($this->toUnserialize[$field])) {
                    throw new IntegrityException("Required conditions field '$field' missing'");
                }

                foreach ($cases as $case => $rule) {
                    if ($this->toUnserialize[$field] == $case) {
                        return $this->walkDeserializedData($rule, $data, $dotPath);
                    }
                }

                if (is_null($defaultCase)) {
                    throw new IntegrityException("No conditions matched, and no default case provided");
                } else {
                    return $this->walkDeserializedData($rule, $data, $dotPath);
                }
            } elseif ($template->isDeep()) {
                $finders = $template->getValue();
                $newData = $data;
                $dotData = Arr::dot($data);

                foreach ($finders as $pattern => $rule) {
                    foreach ($dotData as $innerDotPath => $innerDotValue) {
                        // Back up one step if we're on ._ref
                        if (preg_match("/\\._ref$/", $innerDotPath) === 1) {
                            $innerDotPath = substr($innerDotPath, 0, -1 * strlen("._ref"));
                            $innerDotValue = ["_ref" => $innerDotValue];
                        }

                        if (preg_match($pattern, $innerDotPath) === 1) {

                            Arr::set($newData, $innerDotPath, $this->walkDeserializedData($rule, $innerDotValue, $this->mergeDotPaths($dotPath, $innerDotPath)));
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