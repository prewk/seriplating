<?php

namespace Prewk\Seriplating\Contracts;

/**
 * A deserializer
 */
interface DeserializerInterface
{
    /**
     * Deserialize according to the rules set up in the template into the repository
     *
     * @param array $template Seriplater template
     * @param mixed $repository Target repository
     * @param array $toDeserialize Serialized data
     * @param array $inherited Inherited data from a parent entity
     * @param string $primaryKeyField Name of primary key field
     * @return array The created entity
     */
    public function deserialize(array $template, $repository, array $toDeserialize, array $inherited = [], $primaryKeyField = "id");
}