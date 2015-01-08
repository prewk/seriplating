<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\RuleInterface;

/**
 * A seriplater rule
 */
class Rule implements RuleInterface
{
    /**
     * @var int
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Set type
     *
     * @param int $type Type
     * @return RuleInterface Chainable
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set value
     *
     * @param mixed $value Value
     * @return RuleInterface Chainable
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Rule factory
     *
     * @param int $type Type
     * @param mixed $value Value
     * @return RuleInterface A new rule
     */
    public function make($type, $value = null)
    {
        $product = new static;
        $product
            ->setType($type)
            ->setValue($value);

        return $product;
    }

    /**
     * Get value
     *
     * @return mixed Value
     */
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