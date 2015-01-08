<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Describes a class that can provide a template and can serialize/deserialize with given array data
 */
interface BidirectionalTemplateInterface
{
    /**
     * Get a Seriplater template
     *
     * @return array
     */
    public function getTemplate();

    /**
     * Serialize the given data
     *
     * @param array $toSerialize The unserialized raw data from a database
     * @return array The serialized array
     */
    public function serialize(array $toSerialize);

    /**
     * Deserialize the given data and create the appropriate repository entities
     *
     * @param array $toDeserialize The serialized array to deserialize
     * @param array $inherited Data inherited from a parent entity
     * @return void The created entity in the repository
     */
    public function deserialize(array $toDeserialize, array $inherited = []);
}