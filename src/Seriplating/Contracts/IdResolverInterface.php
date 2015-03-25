<?php

namespace Prewk\Seriplating\Contracts;

interface IdResolverInterface
{
    /**
     * Bind an encountered internal id to a created database id to be able to resolve references later
     *
     * @param int $internalId The internal id encountered
     * @param int|string $dbId The database id to resolve into
     * @throws DataIntegrityException if the internal id already is bound to a value
     * @return void
     */
    public function bind($internalId, $dbId);

    /**
     * Save an/several encountered reference(s) for later resolution
     *
     * @param string|string[] $internalId The internal id reference or an array of internal id references
     * @param callable $updateHandler A callable with which to perform the update
     * @return void
     */
    public function deferCustom($internalId, callable $updateHandler);

    /**
     * Save an encountered reference for later resolution
     *
     * @param mixed $internalId The internal id reference dependency needed to resolve
     * @param mixed $repository Target repository
     * @param mixed $primaryKey Primary key used for updating the repository
     * @param string $field Field, in dot notation, to receive the update
     * @param array $initialEntityData Initial entity data used to not overwrite "deep" fields
     * @param null $fallback Optional fallback instead of exception when relation couldn't be resolved
     * @return void
     */
    public function defer($internalId, $repository, $primaryKey, $field, $initialEntityData = [], $fallback = null);

    /**
     * Register callbacks on resolve of certain internal id names
     *
     * @param mixed $internalId The internal id reference
     * @param callable $eventHandler A callable with which to handle the event
     * @return void
     */
    public function onResolve($internalId, callable $eventHandler);

    /**
     * Resolve the saved encountered references by performing the deferred updates
     *
     * @throws DataIntegrityException if an internal id couldn't be resolved
     * @return void
     */
    public function resolve();
}