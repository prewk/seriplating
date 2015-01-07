<?php

namespace Prewk\Seriplating\Contracts;

interface SerializerInterface
{
    public function serialize(array $template, array $toSerialize);
}