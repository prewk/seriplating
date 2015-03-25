<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\SeriplaterInterface;
use Prewk\Seriplating\Contracts\RuleInterface;

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
    const INCREMENTS = 512;
    const NULLABLE = 1024;

    protected $rule;

    protected $nextIsCollection = false;
    protected $nextIsOptional = false;
    protected $nextIsNullable = false;

    public function __construct(Rule $rule)
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

    /**
     * Consecutive rule will refer to a nullable field (Only works for references)
     *
     * @return $this
     */
    public function nullable()
    {
        $this->nextIsNullable = true;

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

        if ($this->nextIsNullable) {
            $type += self::NULLABLE;
            $this->nextIsNullable = false;
        }

        return $type;
    }

    /**
     * This field is the primary key for this entity
     *
     * @param string $entityName Entity name
     * @return RuleInterface
     */
    public function id($entityName)
    {
        return $this->rule->make($this->applyModifiers(self::ID), $entityName);
    }

    /**
     * This field is a value
     *
     * @return RuleInterface
     */
    public function value()
    {
        return $this->rule->make($this->applyModifiers(self::VALUE));
    }

    /**
     * This field is a reference to another entity
     *
     * @param string $entityName Name of the entity
     * @param null $fallback Optional fallback, if provided the resolver won't throw an exception
     * @return RuleInterface
     */
    public function references($entityName, $fallback = null)
    {
        return $this->rule->make($this->applyModifiers(self::REFERENCES), [
            "entityName" => $entityName,
            "fallback" => $fallback,
        ]);
    }

    /**
     * Treat field differently depending on the case with optional default case
     *
     * @param string $field What field to compare against
     * @param string[] $cases Cases to compare field against
     * @param RuleInterface $defaultCase An optional default case
     * @return RuleInterface
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
     * @return RuleInterface
     */
    public function deep(array $finders)
    {
        return $this->rule->make($this->applyModifiers(self::DEEP), $finders);
    }

    /**
     * Define a relation for use in a hierarchical manner
     *
     * @param string $entityName Name of related entity
     * @return RuleInterface
     */
    public function hasMany($entityName)
    {
        return $this->rule->make($this->applyModifiers(self::HAS_MANY), $entityName);
    }

    /**
     * Field passed from parent above, arguments are a priority list starting with
     * the most prioritized, fallbacks to the next etc
     *
     * @param string ... list of fields
     * @return RuleInterface
     */
    public function inherits()
    {
        return $this->rule->make($this->applyModifiers(self::INHERITS), func_get_args());
    }

    /**
     * When deserializing hierarchically, the parent sets the child's field incrementally
     *
     * @param int $start Start
     * @param int $increment Increment
     * @return RuleInterface
     */
    public function increments($start = 0, $increment = 1)
    {
        return $this->rule->make($this->applyModifiers(self::INCREMENTS), [
            "start" => $start,
            "increment" => $increment,
        ]);
    }
}

