<?php

namespace Prewk\Seriplating\Contracts;

interface RuleInterface
{
    public function setType($type);

    public function setValue($value);

    public function make($type, $value = null);

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
}