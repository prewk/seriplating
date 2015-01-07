<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Manages the internal ids in a serialized structure, to be able to properly connect one entity to another anonymously
 */
interface IdFactoryInterface
{
    /**
     * Produce an unique - or get a pre-existing - id depending on whether it has been requested before
     *
     * @param string $entityName Type of id, typically a database table name
     * @param int $dbId The corresponding "real" id, typically a database table primary key
     * @return int An internal id
     */
    public function get($entityName, $dbId);
}