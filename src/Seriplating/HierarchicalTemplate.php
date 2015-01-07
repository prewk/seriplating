<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\BidirectionalTemplateInterface;
use Prewk\Seriplating\Contracts\HierarchicalInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Errors\HierarchicalCompositionException;

class HierarchicalTemplate implements HierarchicalInterface
{
    /**
     * @var array
     */
    protected $templateRegistry = [];

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
    }
}