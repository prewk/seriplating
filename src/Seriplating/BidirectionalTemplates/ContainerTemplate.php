<?php

namespace Prewk\Seriplating\BidirectionalTemplates;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\IdFactoryInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\IntegrityException;
use Prewk\Seriplating\IdResolver;

/**
 * Provides a Seriplater template for only containing other child templates without requiring a repository
 */
class ContainerTemplate implements BidirectionalTemplateInterface
{
    /**
     * @var array
     */
    protected $template;

    /**
     * @var IdFactoryInterface
     */
    protected $idFactory;

    /**
     * @var IdResolver
     */
    protected $idResolver;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @param IdFactoryInterface $idFactory
     * @param IdResolver $idResolver
     * @param array $template
     * @param null $id
     */
    public function __construct(
        IdFactoryInterface $idFactory,
        IdResolver $idResolver,
        array $template,
        $id = null
    )
    {
        $this->idFactory = $idFactory;
        $this->idResolver = $idResolver;
        $this->template = $template;
        $this->id = $id;
    }

    /**
     * Get a Seriplater template
     *
     * @return array
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Serialize the given data
     *
     * @param array $toSerialize The unserialized raw data from a database
     * @return array The serialized array
     * @throws IntegrityException
     */
    public function serialize(array $toSerialize)
    {
        $container = [];
        foreach ($this->template as $key => $rule) {
            if (
                $rule instanceof RuleInterface &&
                $rule->isId()
            ) {
                if (isset($toSerialize[$key])) {
                    $container["_id"] = $this->idFactory->get($rule->getValue(), $toSerialize[$key]);
                    break;
                } else {
                    throw new IntegrityException("Couldn't find the id rule for the serialization");
                }
            }
        }

        return $container;
    }

    /**
     * Deserialize the given data
     *
     * @param array $toDeserialize The serialized array to deserialize
     * @param array $inherited Data inherited from a parent entity (ignored)
     * @return array
     * @throws IntegrityException
     */
    public function deserialize(array $toDeserialize, array $inherited = [])
    {
        $container = [];

        foreach ($this->template as $key => $rule) {
            if (
                $rule instanceof RuleInterface &&
                $rule->isId()
            ) {
                if (isset($toDeserialize["_id"]) && !is_null($this->id)) {
                    $this->idResolver->bind($toDeserialize["_id"], $this->id);
                    $container[$key] = $this->id;
                    break;
                } else {
                    throw new IntegrityException("Couldn't find the id rule or value for the deserialization");
                }
            }
        }

        return $container;
    }
}