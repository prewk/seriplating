<?php

namespace Prewk\Seriplating\Contracts;

/**
 * A serializer
 */
interface SerializerInterface
{
    /**
     * Serialize according to the rules set up in the template into an array
     *
     * @param array $template Seriplater template
     * @param array $toSerialize Raw data to serialize
     * @return array Serialized data
     */
    public function serialize(array $template, array $toSerialize);
}