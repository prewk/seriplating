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

    /**
     * Deserialize according to the rules set up in the template into the repository
     *
     * @param array $template Seriplater template
     * @param mixed $repository Target repository
     * @param array $toDeserialize Serialized data
     * @param array $inherited Inherited data from a parent entity
     * @param string $primaryKeyField Name of primary key field
     * @return array The created entity
     */
    public function deserialize(array $template, $repository, array $toDeserialize, array $inherited = [], $primaryKeyField = "id")
    {
        foreach ($template as $field => $rule) {
            if (
                $rule instanceof RuleInterface &&
                $rule->isId() &&
                isset($toDeserialize["_id"]) &&
                isset($toDeserialize[$primaryKeyField])
            ) {
                $this->idResolver->bind($toDeserialize["_id"], $toDeserialize[$primaryKeyField]);
            }
        }
    }
}