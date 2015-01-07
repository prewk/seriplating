<?php

namespace Prewk;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;

abstract class AbstractSeriplater implements BidirectionalTemplateInterface
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

    public function serialize(array $toSerialize)
    {
        return $this->genericSerializer->serialize($this->getTemplate(), $toSerialize);
    }


    public function deserialize(RepositoryInterface $repository, array $toUnserialize)
    {
        return $this->genericDeserializer->deserialize($this->getTemplate(), $repository, $toUnserialize);
    }
}