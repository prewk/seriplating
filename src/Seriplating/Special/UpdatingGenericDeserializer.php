<?php

namespace Prewk\Seriplating\Special;

use Prewk\Seriplating\Errors\IntegrityException;
use Prewk\Seriplating\GenericDeserializer;

class UpdatingGenericDeserializer extends GenericDeserializer
{
    private $primaryKey = null;

    public function setPrimaryKey($id)
    {
        $this->primaryKey = $id;
    }

    /**
     * Update the entity in the repository and return results
     *
     * @param mixed $repository Target repository with update(id, data) and get(id) type signature
     * @param array $entityData Entity data to create
     * @return array
     * @throws IntegrityException if a primary key wasn't set
     */
    protected function repositoryAction($repository, array $entityData)
    {
        if (is_null($this->primaryKey)) {
            throw new IntegrityException("The UpdatingGenericDeserializer requires a primary key set before being used");
        }
        // Perform update
        $repository->update($this->primaryKey, $entityData);

        // Return the updated data
        return $repository->get($this->primaryKey);
    }
}