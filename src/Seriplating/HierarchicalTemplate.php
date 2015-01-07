<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\HierarchicalInterface;
use Prewk\Seriplating\Contracts\IdResolverInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\HierarchicalCompositionException;

class HierarchicalTemplate implements HierarchicalInterface
{
    /**
     * @var array
     */
    protected $templateRegistry = [];

    /**
     * @var
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

    public function serialize($entityName, array $unserializedTree)
    {
        if (!isset($this->templateRegistry[$entityName])) {
            throw new HierarchicalCompositionException("Entity '$entityName' wasn't found in the registry");
        }

        // Perform serialization recursively
        $serialization = $this->serializeRelations($this->templateRegistry[$entityName], $unserializedTree);

        return $serialization;
    }

    protected function serializeRelations(BidirectionalTemplateInterface $template, array $data)
    {
        // Serialize this template
        $serialization = $template->serialize($data);

        // Find relations
        foreach ($template as $field => $rule) {
            if ($rule instanceof RuleInterface && $rule->isHasMany()) {
                $relatedEntityName = $rule->getValue();

                if (!isset($this->templateRegistry[$relatedEntityName])) {
                    throw new HierarchicalCompositionException("Related entity '$relatedEntityName' wasn't found in the registry");
                }

                if (!isset($data[$field])) {
                    throw new HierarchicalCompositionException("Related entity '$relatedEntityName's data didn't exist where it was expected");
                }

                $serialization[$field] = $this->serializeRelations($this->templateRegistry[$relatedEntityName], $data[$field]);
            }
        }

        return $serialization;
    }
}