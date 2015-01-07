<?php

namespace Prewk\Seriplating;

use Illuminate\Support\Arr;
use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\IntegrityException;

class GenericDeserializer implements DeserializerInterface
{
    /**
     * @var IdResolverInterface
     */
    protected $idResolver;

    /**
     * @var string
     */
    protected $idName;

    /**
     * @var array
     */
    protected $updatesToDefer = [];

    /**
     * @param IdResolverInterface $idResolver
     */
    public function __construct(
        IdResolverInterface $idResolver
    )
    {
        $this->idResolver = $idResolver;
    }

    public function deserialize(array $template, RepositoryInterface $repository, array $toUnserialize, $primaryKeyField = "id")
    {
        $entityData = $this->walkDeserializedData($template, $toUnserialize);

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
            } else {
                throw new IntegrityException("Invalid template rule");
            }
        }
    }
}