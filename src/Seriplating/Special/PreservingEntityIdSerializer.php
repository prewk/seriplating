<?php

namespace Prewk\Seriplating\Special;

use Prewk\Seriplating\Contracts\IdFactoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;

/**
 * A deserializer that only preserves the id field
 * @TODO Extend GenericSerializer and preserve the id field
 */
class PreservingEntityIdSerializer implements SerializerInterface
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

    /**
     * Serialize according to the rules set up in the template into an array
     *
     * @param array $template Seriplater template
     * @param array $toSerialize Raw data to serialize
     * @return array Serialized data
     */
    public function serialize(array $template, array $toSerialize)
    {
        $entityData = [];

        // Find id field
        foreach ($template as $field => $rule) {
            if (
                $rule instanceof RuleInterface &&
                $rule->isId() &&
                isset($toSerialize[$field])
            ) {
                $entityData["_id"] = $this->idFactory->get($rule->getValue(), $toSerialize[$field]);
                $entityData[$field] = $toSerialize[$field];
            }
        }

        return $entityData;
    }
}