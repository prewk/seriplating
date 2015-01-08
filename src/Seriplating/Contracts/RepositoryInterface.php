<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Represents a repository
 */
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
     * Update an entity's fields
     *
     * @param mixed $id The primary key of the entity
     * @param array $data Fields and their data
     * @return array The updated entity
     */
    public function update($id, array $data);
}