<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Describes a hierarchical system for serializing and deserializing
 */
interface HierarchicalInterface
{
    /**
     * Register a bidirectional serializer with an id to be able to use it hierarchically
     * @param BidirectionalTemplateInterface $serializer Serializer/Deserializer/Template
     * @return HierarchicalInterface Chainable
     */
    public function register(BidirectionalTemplateInterface $serializer);

    /**
     * Serialize from the given (de)serializer id entity name and downwards
     *
     * @param string $entityName The registrered entity's name as provided via the id() rule
     * @param array $unserializedTree Unserialized data
     * @return array Serialized data
     */
    public function serialize($entityName, array $unserializedTree);

    /**
     * Deserialize from the given (de)serializer id entity name and downwards
     *
     * @param string $entityName The registrered entity's name as provided via the id() rule
     * @param array $serializedTree Serialized data
     * @return array Unserialized entity data
     */
    public function deserialize($entityName, array $serializedTree);
}