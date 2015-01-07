<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\DeserializerInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;

class GenericDeserializer implements DeserializerInterface
{
    public function deserialize(array $template, RepositoryInterface $repository, array $toUnserialize)
    {
        // TODO: Implement deserialize() method.
    }
}