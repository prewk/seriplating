<?php

namespace Prewk\Seriplating;

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

        if (isset($this->idName)) {
            $this->idResolver->bind($this->idName, $createdEntity[$primaryKeyField]);
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
            } else {
                throw new IntegrityException("Invalid template rule");
            }
        }
    }
}