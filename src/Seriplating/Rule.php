<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\RuleInterface;

class Rule implements RuleInterface
{
    protected $type;
    protected $value;

    public function __construct() {}

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function make($type, $value = null)
    {
        $product = new static();
        $product
            ->setType($type)
            ->setValue($value);

        return $product;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isId()
    {
        return (Seriplater::ID & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return (Seriplater::OPTIONAL & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        return (Seriplater::COLLECTION_OF & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isValue()
    {
        return (Seriplater::VALUE & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isReference()
    {
        return (Seriplater::REFERENCES & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isConditions()
    {
        return (Seriplater::CONDITIONS & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isDeep()
    {
        return (Seriplater::DEEP & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isHasMany()
    {
        return (Seriplater::HAS_MANY & $this->type) !== 0;
    }

    /**
     * @return bool
     */
    public function isInherited()
    {
        return (Seriplater::INHERITS & $this->type) !== 0;
    }
}