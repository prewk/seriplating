<?php

namespace Prewk\Seriplating\Special;

use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;

/**
 * A deserializer that doesn't create or update the repository, it just expects an id rule, an _id field, and a id field
 * to bind pre-existing entities to the resolver.
 */
class PreExistingEntityDeserializer implements DeserializerInterface
{
    /**
     * @var IdResolverInterface
     */
    protected $idResolver;

    /**
     * @param IdResolverInterface $idResolver
     */
    public function __construct(
        IdResolverInterface $idResolver
    )
    {
        $this->idResolver = $idResolver;
    }

    public function deserialize(array $template, RepositoryInterface $repository, array $toUnserialize, array $inherited = [], $primaryKeyField = "id")
    {
        foreach ($template as $field => $rule) {
            if (
                $rule instanceof RuleInterface &&
                $rule->isId() &&
                isset($toUnserialize["_id"]) &&
                isset($toUnserialize[$primaryKeyField])
            ) {
                $this->idResolver->bind($toUnserialize["_id"], $toUnserialize[$primaryKeyField]);
            }
        }
    }
}