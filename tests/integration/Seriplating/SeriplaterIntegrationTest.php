<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;
use Mockery;

class SeriplaterIntegrationTest extends SeriplatingTestCase
{
    private $seriplater;

    public function setUp()
    {
        $this->seriplater = new Seriplater(new Rule);
    }

    public function test_value()
    {
        $rule = $this->seriplater->value();

        $this->assertTrue($rule->isValue());
    }

    public function test_id()
    {
        $rule = $this->seriplater->id("foos");

        $this->assertTrue($rule->isId());
    }

    public function test_references()
    {
        $rule = $this->seriplater->references("foo");

        $this->assertTrue($rule->isReference());
    }

    public function test_conditions()
    {
        $rule = $this->seriplater->conditions("foo", [
            "bar" => "baz",
        ]);

        $this->assertTrue($rule->isConditions());
    }

    public function test_deep()
    {
        $rule = $this->seriplater->deep([
            "bar" => "baz",
        ]);

        $this->assertTrue($rule->isDeep());
    }

    public function test_modifiers()
    {
        $rule1 = $this->seriplater->optional()->value();
        $rule2 = $this->seriplater->collectionOf()->value();
        $rule3 = $this->seriplater->optional()->collectionOf()->value();

        $this->assertTrue($rule1->isOptional());
        $this->assertFalse($rule1->isCollection());
        $this->assertTrue($rule2->isCollection());
        $this->assertFalse($rule2->isOptional());
        $this->assertTrue($rule3->isOptional());
        $this->assertTrue($rule3->isCollection());
    }

    public function test_has_many()
    {
        $rule = $this->seriplater->hasMany("foos");

        $this->assertTrue($rule->isHasMany());
    }

    public function test_inherits()
    {
        $rule = $this->seriplater->inherits("foos");

        $this->assertTrue($rule->isInherited());
    }
}