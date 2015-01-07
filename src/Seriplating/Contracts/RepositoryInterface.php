<?php

namespace Prewk\Seriplating\Contracts;

interface RepositoryInterface
{
    /**
     * Create an entity
     *
     * @param array $data Data to create with
     * @return mixed The primary key of the created entity
     */
    public function create(array $data);

    /**
     * Update an entity's field
     *
     * @param mixed $id The primary key of the entity
     * @param string $field Field to update
     * @param mixed $data Data to update with
     */
    public function update($id, $field, $data);
}