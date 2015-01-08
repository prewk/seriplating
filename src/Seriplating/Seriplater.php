<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\SeriplaterInterface;
use Prewk\Seriplating\Contracts\RuleInterface;
use Prewk\Seriplating\Contracts\SeriplaterRuleInterface;

class Seriplater implements SeriplaterInterface
{
    const COLLECTION_OF = 1;
    const OPTIONAL = 2;
    const ID = 4;
    const VALUE = 8;
    const REFERENCES = 16;
    const CONDITIONS = 32;
    const DEEP = 64;
    const HAS_MANY = 128;
    const INHERITS = 256;

    protected $rule;

    protected $nextIsCollection = false;
    protected $nextIsOptional = false;

    public function __construct(RuleInterface $rule)
    {
        $this->rule = $rule;
    }

    /**
     * Consecutive rule will refer to an collection
     *
     * @param null|string $childSortField Consecutive rule has a sort field to set
     * @return SeriplaterInterface
     */
    public function collectionOf($childSortField = null)
    {
        $this->nextIsCollection = true;

        return $this;
    }

    /**
     * Consecutive rule will refer to an optional field
     *
     * @return SeriplaterInterface
     */
    public function optional()
    {
        $this->nextIsOptional = true;

        return $this;
    }

    protected function applyModifiers($type)
    {
        if ($this->nextIsCollection) {
            $type += self::COLLECTION_OF;
            $this->nextIsCollection = false;
        }

        if ($this->nextIsOptional) {
            $type += self::OPTIONAL;
            $this->nextIsOptional = false;
        }

        return $type;
    }

    /**
     * This field is the primary key for this entity
     *
     * @param string $entityName Entity name
     * @return SeriplaterRuleInterface
     */
    public function id($entityName)
    {
        return $this->rule->make($this->applyModifiers(self::ID), $entityName);
    }

    /**
     * This field is a value
     *
     * @return SeriplaterRuleInterface
     */
    public function value()
    {
        return $this->rule->make($this->applyModifiers(self::VALUE));
    }

    /**
     * This field is a reference to another entity
     *
     * @param string $entityName Name of the entity
     * @return SeriplaterRuleInterface
     */
    public function references($entityName)
    {
        return $this->rule->make($this->applyModifiers(self::REFERENCES), $entityName);
    }

    /**
     * Treat field differently depending on the case with optional default case
     *
     * @param string $field What field to compare against
     * @param string[] $cases Cases to compare field against
     * @param RuleInterface $defaultCase An optional default case
     * @return SeriplaterRuleInterface
     */
    public function conditions($field, array $cases, RuleInterface $defaultCase = null)
    {
        return $this->rule->make($this->applyModifiers(self::CONDITIONS), [
            "field" => $field,
            "cases" => $cases,
            "defaultCase" => $defaultCase,
        ]);
    }

    /**
     * Define deep rules with regular expressions using array dot notation
     *
     * @param array $finders Key-value array with regexp-rule
     * @return SeriplaterRuleInterface
     */
    public function deep(array $finders)
    {
        return $this->rule->make($this->applyModifiers(self::DEEP), $finders);
    }

    /**
     * Define a relation for use in a hierarchical manner
     *
     * @param string $entityName Name of related entity
     * @return SeriplaterRuleInterface
     */
    public function hasMany($entityName)
    {
        return $this->rule->make(self::HAS_MANY, $entityName);
    }

    /**
     * Field passed from parent above
     *
     * @param string $field Field to inherit
     * @return SeriplaterRuleInterface
     */
    public function inherits($field)
    {
        return $this->rule->make(self::INHERITS, $field);
    }
}

