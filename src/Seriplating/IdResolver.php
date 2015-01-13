<?php

namespace Prewk\Seriplating;

use Illuminate\Support\Arr;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Errors\IntegrityException;
use SplObjectStorage;

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
    protected $customDeferred = [];

    /**
     * @var SplObjectStorage
     */
    protected $deferred;

    public function __construct()
    {
        $this->deferred = new SplObjectStorage;
    }

    /**
     * Bind an encountered internal id to a created database id to be able to resolve references later
     *
     * @param int $internalId The internal id encountered
     * @param int|string $dbId The database id to resolve into
     * @throws IntegrityException if the internal id already is bound to a value
     */
    public function bind($internalId, $dbId)
    {
//        echo "BOUND $internalId TO $dbId\n";
        if (isset($this->internalIdToDbId[$internalId])) {
            throw new IntegrityException("The internal id was already bound to a value");
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
    public function deferCustom($internalId, callable $updateHandler)
    {
        $this->customDeferred[] = [
            "ids" => !is_array($internalId) ? [$internalId] : $internalId,
            "handler" => $updateHandler,
        ];
    }

    /**
     * Resolve the saved encountered references by performing the deferred updates
     *
     * @throws IntegrityException if an internal id couldn't be resolved
     * @return void
     */
    public function resolve()
    {
        // Iterate through the deferred updates
        foreach ($this->deferred as $repository) {
            // Some SplObjectStorage weirdness
            $deferredRecords = $this->deferred[$repository];
            // Construct a full update array for this repository, index by primary key
            $update = [];
            foreach ($deferredRecords as $record) {
                if (!isset($update[$record["primaryKey"]])) {
                    $update[$record["primaryKey"]] = [];
                }

                // Get resolved db id
                if (!isset($this->internalIdToDbId[$record["internalId"]])) {
                    // Fallback ?
                    if (!is_null($record["fallback"])) {
                        // Yep
                        $dbId = $record["fallback"];
                    } else {
                        // Nope, crash, the internal id is missing from our lookup table
                        throw new IntegrityException("An internal id couldn't be resolved: " . $record["internalId"]);
                    }
                } else {
                    $dbId = $this->internalIdToDbId[$record["internalId"]];
                }

                // Get root field from possible dot notation
                // Root field = Table.field
                // Deep field = Table.field.foo.bar.0.baz
                $dotParts = explode(".", $record["field"]);
                $rootField = $dotParts[0];
                $deepField = count($dotParts) > 1;

                // If this is a deep field, try not to overwrite everything
                if ($deepField) {
                    // If the root field isn't set, set it..
                    if (!isset($update[$record["primaryKey"]][$rootField])) {
                        if (isset($record["initialEntityData"], $record["initialEntityData"][$rootField])) {
                            // ..with the initial entity data's corresponding root field
                            $update[$record["primaryKey"]][$rootField] = $record["initialEntityData"][$rootField];
                        } else {
                            // ..with an empty array
                            $update[$record["primaryKey"]][$rootField] = [];
                        }
                    }
                }

//                // @TODO: Optimization, sometimes fallbacks are used and no update needs to be made at all
//                if (isset($record["initialEntityData"], $record["initialEntityData"][$record["field"]]) &&
//                    $record["initialEntityData"][$record["field"]] === $dbId
//                ) {
//
//                }

                // Now, update with the whole deep dot notation, using the resolved db id as a value
                Arr::set($update, $record["primaryKey"] . "." . $record["field"], $dbId);
            }

            // Perform the updates
            foreach ($update as $primaryKey => $update) {
                $repository->update($primaryKey, $update);
            }
        }

        // Iterate through the custom update handlers
        foreach ($this->customDeferred as $deferred) {
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
                    throw new IntegrityException("An internal id couldn't be resolved: " . $internalId);
                }

                $dbIds[] = $this->internalIdToDbId[$internalId];
            }

            // Run the handler
            call_user_func_array($deferred["handler"], $dbIds);
        }
    }

    /**
     * Save an encountered reference for later resolution
     *
     * @param mixed $internalId The internal id reference dependency needed to resolve
     * @param RepositoryInterface $repository Target repository
     * @param mixed $primaryKey Primary key used for updating the repository
     * @param string $field Field, in dot notation, to receive the update
     * @param array $initialEntityData Initial entity data used to not overwrite "deep" fields
     * @param null $fallback Optional fallback instead of exception when relation couldn't be resolved
     * @return void
     */
    public function defer($internalId, RepositoryInterface $repository, $primaryKey, $field, $initialEntityData = [], $fallback = null)
    {
        if (!isset($this->deferred[$repository])) {
            $this->deferred[$repository] = [];
        }

        $records = $this->deferred[$repository];

        $records[] = [
            "internalId" => $internalId,
            "primaryKey" => $primaryKey,
            "field" => $field,
            "initialEntityData" => $initialEntityData,
            "fallback" => $fallback,
        ];

        $this->deferred[$repository] = $records;
    }
}