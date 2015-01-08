<?php

namespace Prewk\Seriplating\Contracts;

/**
 * A seriplater rule
 */
interface RuleInterface
{
    /**
     * Set type
     *
     * @param int $type Type
     * @return RuleInterface Chainable
     */
    public function setType($type);

    /**
     * Set value
     *
     * @param mixed $value Value
     * @return RuleInterface Chainable
     */
    public function setValue($value);

    /**
     * Rule factory
     *
     * @param int $type Type
     * @param mixed $value Value
     * @return RuleInterface A new rule
     */
    public function make($type, $value = null);

    /**
     * Get value
     *
     * @return mixed Value
     */
    public function getValue();

    /**
     * @return bool
     */
    public function isId();

    /**
     * @return bool
     */
    public function isOptional();

    /**
     * @return bool
     */
    public function isCollection();

    /**
     * @return bool
     */
    public function isValue();

    /**
     * @return bool
     */
    public function isReference();

    /**
     * @return bool
     */
    public function isConditions();

    /**
     * @return bool
     */
    public function isDeep();

    /**
     * @return bool
     */
    public function isHasMany();

    /**
     * @return bool
     */
    public function isInherited();
}