<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Templating system for serializing database entities and their relations
 */
interface SeriplaterInterface
{
    /**
     * Consecutive rule will refer to an collection
     *
     * @param null|string $childSortField Consecutive rule has a sort field to set
     * @return SeriplaterInterface
     */
    public function collectionOf($childSortField = null);

    /**
     * Consecutive rule will refer to an optional field
     *
     * @return SeriplaterInterface
     */
    public function optional();

    /**
     * This field is the primary key for this entity
     *
     * @param string $entityName Entity name
     * @return RuleInterface
     */
    public function id($entityName);

    /**
     * This field is a value
     *
     * @return RuleInterface
     */
    public function value();

    /**
     * This field is a reference to another entity
     *
     * @param string $entityName Name of the entity
     * @return RuleInterface
     */
    public function references($entityName);

    /**
     * Treat field differently depending on the case with optional default case
     *
     * @param string $field What field to compare against
     * @param string[] $cases Cases to compare field against
     * @param RuleInterface $defaultCase An optional default case
     * @return RuleInterface
     */
    public function conditions($field, array $cases, RuleInterface $defaultCase = null);

    /**
     * Define deep rules with regular expressions using array dot notation
     *
     * @param array $finders Key-value array with regexp-rule
     * @return RuleInterface
     */
    public function deep(array $finders);

    /**
     * Define a relation for use in a hierarchical manner
     *
     * @param string $entityName Name of related entity
     * @return RuleInterface
     */
    public function hasMany($entityName);

    /**
     * Field passed from parent above, arguments are a priority list starting with
     * the most prioritized, fallbacks to the next etc
     *
     * @return RuleInterface
     */
    public function inherits();
}