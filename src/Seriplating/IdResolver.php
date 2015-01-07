<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\IdResolverInterface;

/**
 * Collects references to internal ids in a deserialization to connect them together after creation
 */
class IdResolver implements IdResolverInterface
{
    /**
     * @var array
     */
    protected $internalIdToDbId = [];

    /**
     * @var array
     */
    protected $deferredResolutions = [];

    /**
     * @var array
     */
    protected $deferred = [];

    /**
     * Bind an encountered internal id to a created database id to be able to resolve references later
     *
     * @param int $internalId The internal id encountered
     * @param int|string $dbId The database id to resolve into
     * @throws DataIntegrityException if the internal id already is bound to a value
     * @return void
     */
    public function bind($internalId, $dbId)
    {
        if (isset($this->internalIdToDbId[$internalId])) {
            throw new DataIntegrityException("The internal id was already bound to a value");
        }

        $this->internalIdToDbId[$internalId] = $dbId;
    }

    /**
     * Save an/several encountered reference(s) for later resolution
     *
     * @param string|string[] $internalId The internal id reference or an array of internal id references
     * @param callable $updateHandler A callable with which to perform the update
     * @return void
     */
    public function deferResolution($internalId, callable $updateHandler)
    {
        $this->deferred[] = [
            "ids" => !is_array($internalId) ? [$internalId] : $internalId,
            "handler" => $updateHandler,
        ];
    }

    /**
     * Resolve the saved encountered references by performing the deferred updates
     *
     * @throws DataIntegrityException if an internal id couldn't be resolved
     * @return void
     */
    public function resolve()
    {
        // Iterate through the update handlers
        foreach ($this->deferred as $deferred) {
            // Gather db ids
            $dbIds = [];
            foreach ($deferred["ids"] as $internalId) {
                // Support null refs
                if (is_null($internalId)) {
                    $dbIds[] = null;
                    continue;
                }

                if (!isset($this->internalIdToDbId[$internalId])) {
                    // Crash if the internal ids are missing from our lookup table
                    throw new DataIntegrityException("An internal id couldn't be resolved: " . $internalId);
                }

                $dbIds[] = $this->internalIdToDbId[$internalId];
            }

            // Run the handler
            call_user_func_array($deferred["handler"], $dbIds);
        }
    }
}