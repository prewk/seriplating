<?php

namespace Prewk;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Contracts\SerializerInterface;

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
     * @var array
     */
    protected $template;

    /**
     * @param SerializerInterface $genericSerializer
     * @param DeserializerInterface $genericDeserializer
     * @param array $template
     */
    public function __construct(
        SerializerInterface $genericSerializer,
        DeserializerInterface $genericDeserializer,
        array $template
    )
    {
        $this->genericSerializer = $genericSerializer;
        $this->genericDeserializer = $genericDeserializer;
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function serialize(array $toSerialize)
    {
        return $this->genericSerializer->serialize($this->getTemplate(), $toSerialize);
    }

    public function deserialize(RepositoryInterface $repository, array $toUnserialize, array $inherited = [])
    {
        return $this->genericDeserializer->deserialize($this->getTemplate(), $repository, $toUnserialize, $inherited);
    }
}