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
    public function deferResolution($internalId, callable $updateHandler);

    /**
     * Resolve the saved encountered references by performing the deferred updates
     *
     * @throws DataIntegrityException if an internal id couldn't be resolved
     * @return void
     */
    public function resolve();
}