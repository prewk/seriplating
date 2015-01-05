<?php

namespace Prewk\Seriplating;

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
     * @return SeriplaterRuleInterface
     */
    public function id($entityName);

    /**
     * This field is a value
     *
     * @return SeriplaterRuleInterface
     */
    public function value();

    /**
     * This field is a reference to another entity
     *
     * @param SerializerInterface $serializer Serializer for another entity
     * @return SeriplaterRuleInterface
     */
    public function references(SerializerInterface $serializer);

    /**
     * Treat field differently depending on the case with optional default case
     *
     * @param string $field What field to compare against
     * @param string[] $cases Cases to compare field against
     * @param SeriplaterRuleInterface $defaultCase An optional default case
     * @return SeriplaterRuleInterface
     */
    public function conditions($field, array $cases, SeriplaterRuleInterface $defaultCase = null);

    /**
     * Define deep rules with regular expressions using array dot notation
     *
     * @param array $finders Key-value array with regexp-rule
     * @return SeriplaterRuleInterface
     */
    public function deep(array $finders);
}