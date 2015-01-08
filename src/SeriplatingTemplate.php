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
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var array
     */
    protected $template;

    /**
     * @param SerializerInterface $genericSerializer
     * @param DeserializerInterface $genericDeserializer
     * @param RepositoryInterface $repository
     * @param array $template
     */
    public function __construct(
        SerializerInterface $genericSerializer,
        DeserializerInterface $genericDeserializer,
        RepositoryInterface $repository,
        array $template
    )
    {
        $this->genericSerializer = $genericSerializer;
        $this->genericDeserializer = $genericDeserializer;
        $this->repository = $repository;
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

    public function deserialize(array $toUnserialize, array $inherited = [])
    {
        return $this->genericDeserializer->deserialize($this->getTemplate(), $this->repository, $toUnserialize, $inherited);
    }
}