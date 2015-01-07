<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;

class GenericSerializerTest extends SeriplatingTestCase
{
    private $seriplater;

    public function setUp()
    {
        $this->seriplater = new Seriplater(
            new Rule
        );
    }

    public function test_value()
    {
        $t = $this->seriplater;

        $template = [
            "foo" => $t->value(),
            "baz" => $t->value(),
        ];
        $entity = [
            "foo" => "bar",
            "baz" => "qux",
        ];
        $expected = $entity;

        $ser = new GenericSerializer;
        $serialized = $ser->serialize($template, $entity);

        $this->assertEquals($expected, $serialized);
    }
}