<?php

namespace Prewk;

use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;

abstract class AbstractSeriplater
{
    /**
     * @var SerializerInterface
     */
    private $genericSerializer;

    /**
     * @var DeserializerInterface
     */
    private $genericDeserializer;

    /**
     * @param SerializerInterface $genericSerializer
     * @param DeserializerInterface $genericDeserializer
     */
    public function __construct(
        SerializerInterface $genericSerializer,
        DeserializerInterface $genericDeserializer
    )
    {
        $this->genericSerializer = $genericSerializer;
        $this->genericDeserializer = $genericDeserializer;
    }

    abstract protected function getTemplate();

    public function serialize(array $toSerialize)
    {
        return $this->genericSerializer($this->getTemplate(), $toSerialize);
    }


    public function deserialize(RepositoryInterface $repository, array $toUnserialize)
    {
        return $this->deserialize($this->getTemplate(), $repository, $toUnserialize);
    }

}