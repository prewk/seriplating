<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\IdFactoryInterface;

/**
 * Manages the internal ids in a serialized structure, to be able to properly connect one entity to another anonymously
 */
class IdFactory implements IdFactoryInterface
{
    /**
     * @var array A [real id] => internal id lookup table
     */
    protected $lookupTable = [];

    /**
     * @var int A unique id incrementer
     */
    protected $uniqueId = 0;

    /**
     * Produce an unique - or get a pre-existing - id depending on whether it has been requested before
     *
     * @param string $entityName Type of id, typically a database table name
     * @param int $dbId The corresponding "real" id, typically a database table primary key
     * @return int An internal id
     */
    public function get($entityName, $dbId)
    {
        if (isset($this->lookupTable[$entityName], $this->lookupTable[$entityName][$dbId])) {
            // Real id already set
            return $this->lookupTable[$entityName][$dbId];
        } else {
            // Real id not set, set and return an unique internal id
            if (!isset($this->lookupTable[$entityName])) {
                $this->lookupTable[$entityName] = [];
            }

            $uniqueId = $this->getUniqueId($entityName, $dbId);

            // Associate the internal id with the real id
            $this->lookupTable[$entityName][$dbId] = $uniqueId;

            // Return our newly created internal id
            return $uniqueId;
        }
    }

    protected function getUniqueId($entityName, $dbId)
    {
//        echo "GET $entityName $dbId " . $this->uniqueId . "\n";
        return $entityName . "_" . $this->uniqueId++;
    }
}