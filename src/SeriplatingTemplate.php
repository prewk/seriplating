<?php

namespace Prewk;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;

/**
 * Provides a Seriplater template and methods for (de)serializing
 */
class SeriplatingTemplate implements BidirectionalTemplateInterface
{
    /**
     * @var SerializerInterface
     */
    protected $genericSerializer;

    /**
     * @var DeserializerInterface
     */
    protected $genericDeserializer;

    /**
     * @var mixed
     */
    protected $repository;

    /**
     * @var array
     */
    protected $template;

    /**
     * @param SerializerInterface $genericSerializer
     * @param DeserializerInterface $genericDeserializer
     * @param mixed $repository
     * @param array $template
     */
    public function __construct(
        SerializerInterface $genericSerializer,
        DeserializerInterface $genericDeserializer,
        $repository,
        array $template
    )
    {
        $this->genericSerializer = $genericSerializer;
        $this->genericDeserializer = $genericDeserializer;
        $this->repository = $repository;
        $this->template = $template;
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
     */
    public function serialize(array $toSerialize)
    {
        return $this->genericSerializer->serialize($this->getTemplate(), $toSerialize);
    }

    /**
     * Deserialize the given data and create the appropriate repository entities
     *
     * @param array $toDeserialize The serialized array to deserialize
     * @param array $inherited Data inherited from a parent entity
     * @return void The created entity in the repository
     */
    public function deserialize(array $toDeserialize, array $inherited = [])
    {
        return $this->genericDeserializer->deserialize($this->getTemplate(), $this->repository, $toDeserialize, $inherited);
    }
}