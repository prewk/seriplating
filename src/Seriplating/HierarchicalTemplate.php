<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\HierarchicalInterface;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\HierarchicalCompositionException;
use Prewk\Seriplating\Errors\IntegrityException;

class HierarchicalTemplate implements HierarchicalInterface
{
    /**
     * @var array
     */
    protected $templateRegistry = [];

    /**
     * @var IdResolverInterface
     */
    protected $idResolver;

    /**
     * @param IdResolverInterface $idResolver
     */
    public function __construct(
        IdResolverInterface $idResolver
    )
    {
        $this->idResolver = $idResolver;
    }

    /**
     * Register a bidirectional serializer with an id to be able to use it hierarchically
     * @param BidirectionalTemplateInterface $serializer Serializer/Deserializer/Template
     * @return HierarchicalInterface Chainable
     * @throws HierarchicalCompositionException on structure errors or missing fields
     */
    public function register(BidirectionalTemplateInterface $serializer)
    {
        // Find id
        $entityName = null;

        foreach ($serializer->getTemplate() as $field => $value) {
            if ($value instanceof RuleInterface && $value->isId()) {
                $entityName = $value->getValue();
                break;
            }
        }

        if (is_null($entityName)) {
            throw new HierarchicalCompositionException("Missing id rule on serializer");
        }

        if (isset($this->templateRegistry[$entityName])) {
            throw new HierarchicalCompositionException("A serializer is already registered with the entity name '$entityName'");
        }

        $this->templateRegistry[$entityName] = $serializer;

        return $this;
    }

    /**
     * Serialize from the given (de)serializer id entity name and downwards
     *
     * @param string $entityName The registered entity's name as provided via the id() rule
     * @param array $unserializedTree Unserialized data
     * @return array Serialized data
     * @throws HierarchicalCompositionException on structure errors or missing fields
     */
    public function serialize($entityName, array $unserializedTree)
    {
        if (!isset($this->templateRegistry[$entityName])) {
            throw new HierarchicalCompositionException("Entity '$entityName' wasn't found in the registry");
        }

        // Perform serialization recursively
        $serialization = $this->serializeRelations($this->templateRegistry[$entityName], $unserializedTree);

        return $serialization;
    }

    /**
     * Serialize recursively
     *
     * @param BidirectionalTemplateInterface $template Serializer/Deserializer/Template
     * @param array $data Scope data
     * @return array Serialized data
     * @throws HierarchicalCompositionException on structure errors or missing fields
     */
    protected function serializeRelations(BidirectionalTemplateInterface $template, array $data)
    {
        // Serialize this template
        $serialization = $template->serialize($data);

        // Find relations
        foreach ($template->getTemplate() as $field => $rule) {
            if ($rule instanceof RuleInterface && $rule->isHasMany()) {
                $relatedEntityName = $rule->getValue();

                if (!isset($this->templateRegistry[$relatedEntityName])) {
                    throw new HierarchicalCompositionException("Related entity '$relatedEntityName' wasn't found in the registry");
                }

                $serialization[$field] = [];

                if (!isset($data[$field]) && $rule->isOptional()) {
                    continue;
                } else if (!isset($data[$field])) {
                    throw new HierarchicalCompositionException("Related entity '$relatedEntityName's data didn't exist where it was expected");
                }

                // Serialize the relations one-by-one
                foreach ($data[$field] as $child) {
                    $serialization[$field][] = $this->serializeRelations($this->templateRegistry[$relatedEntityName], $child);
                }
            }
        }

        return $serialization;
    }

    /**
     * Deserialize from the given (de)serializer id entity name and downwards
     *
     * @param string $entityName The registrered entity's name as provided via the id() rule
     * @param array $serializedTree Serialized data
     * @return Unserialized entity data
     * @throws HierarchicalCompositionException on structure errors or missing fields
     */
    public function deserialize($entityName, array $serializedTree)
    {
        if (!isset($this->templateRegistry[$entityName])) {
            throw new HierarchicalCompositionException("Entity '$entityName' wasn't found in the registry");
        }

        // Perform deserialization recursively
        $entityData = $this->deserializeRelations($this->templateRegistry[$entityName], $serializedTree);

        // Resolve deferred updates
        $this->idResolver->resolve();

        return $entityData;
    }

    /**
     * Deserialize recursively
     *
     * @param BidirectionalTemplateInterface $template Serializer/Deserializer/Template
     * @param array $data Scope data
     * @param array $inherited Inherited data
     * @return array The deserialized entity data
     * @throws IntegrityException if a conditions rule fails
     */
    protected function deserializeRelations(BidirectionalTemplateInterface $template, array $data, array $inherited = [])
    {
        // Deserialize this template
        $entityData = $template->deserialize($data, $inherited);

        // Find relations
        foreach ($template->getTemplate() as $field => $rule) {
            if ($rule instanceof RuleInterface) {
                if ($rule->isHasMany()) {
                    $entityData[$field] = $this->handleHasManyRule($rule, $data, $field, $entityData);
                }
            }
        }

        return $entityData;
    }

    /**
     * Handle encountered hasMany rule
     *
     * @param RuleInterface $rule The hasMany Rule
     * @param mixed $data Scope data
     * @param string $field Owning field
     * @param array $entityData All data
     * @return array The modified data with inheritance sorted out
     * @throws HierarchicalCompositionException if required entities weren't found
     */
    protected function handleHasManyRule(RuleInterface $rule, $data, $field, array $entityData)
    {
        // Has many
        $relatedEntityName = $rule->getValue();

        if (!isset($this->templateRegistry[$relatedEntityName])) {
            throw new HierarchicalCompositionException("Related entity '$relatedEntityName' wasn't found in the registry");
        }

        if (!isset($data[$field])) {
            throw new HierarchicalCompositionException("Related entity '$relatedEntityName's data didn't exist where it was expected");
        }

        // Look ahead at child for increment rules
        $counters = [];
        foreach ($this->templateRegistry[$relatedEntityName]->getTemplate() as $childField => $childRule) {
            if ($childRule instanceof RuleInterface && $childRule->isIncrementing()) {
                $incrementRule = $childRule->getValue();

                $counters[] = [
                    "field" => "@$childField",
                    "current" => $incrementRule["start"],
                    "increment" => $incrementRule["increment"],
                ];
            }
        }


        // Deserialize the relations one-by-one
        $entityField = [];
        foreach ($data[$field] as $child) {
            // Add the increments
            $entityDataWithIncrements = $entityData;
            for ($i = 0; $i < count($counters); $i++) {
                $entityDataWithIncrements[$counters[$i]["field"]] = $counters[$i]["current"];
                $counters[$i]["current"] += $counters[$i]["increment"];
            }

            $entityField[] = $this->deserializeRelations($this->templateRegistry[$relatedEntityName], $child, $entityDataWithIncrements);
        }

        // Return to the parent recursive method
        return $entityField;
    }
}