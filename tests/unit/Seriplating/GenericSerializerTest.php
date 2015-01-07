<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;
use Mockery;

class GenericSerializerTest extends SeriplatingTestCase
{
    private $seriplater;
    private $idFactory;

    public function setUp()
    {
        $this->seriplater = new Seriplater(
            new Rule
        );

        $this->idFactory = Mockery::mock("Prewk\\Seriplating\\Contracts\\IdFactoryInterface");
    }

    public function test_value()
    {
        $t = $this->seriplater;

        $template = [
            "foo" => $t->value(),
            "baz" => $t->value(),
            "lorem" => $t->optional()->value(),
            "ipsum" => $t->optional()->value(),
        ];
        $entity = [
            "foo" => "bar",
            "baz" => "qux",
            "ipsum" => "asdf",
        ];
        $expected = $entity;

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity);

        $this->assertEquals($expected, $serialized);
    }

    public function test_references()
    {
        $t = $this->seriplater;

        $template = [
            "foo_id" => $t->references("foos"),
            "bar_ids" => $t->collectionOf()->references("bars"),
            "baz_id" => $t->optional()->references("bazes"),
        ];
        $entity = [
            "foo_id" => 123,
            "bar_ids" => [1, 2, 3],
        ];
        $expected = [
            "foo_id" => ["_ref" => "foos_0"],
            "bar_ids" => [
                ["_ref" => "bars_0"],
                ["_ref" => "bars_1"],
                ["_ref" => "bars_2"],
            ]
        ];

        $this->idFactory
            ->shouldReceive("get")
            ->with("foos", 123)
            ->andReturn("foos_0")

            ->shouldReceive("get")
            ->with("bars", 1)
            ->andReturn("bars_0")
            ->shouldReceive("get")
            ->with("bars", 2)
            ->andReturn("bars_1")
            ->shouldReceive("get")
            ->with("bars", 3)
            ->andReturn("bars_2");

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity);

        $this->assertEquals($expected, $serialized);
    }

    public function test_id()
    {
        $t = $this->seriplater;

        $template = [
            "id" => $t->id("foos"),
            "bar" => $t->value(),
        ];
        $entity = [
            "id" => 123,
            "bar" => "baz",
        ];
        $expected = [
            "_id" => "foos_0",
            "bar" => "baz",
        ];

        $this->idFactory
            ->shouldReceive("get")
            ->with("foos", 123)
            ->andReturn("foos_0");

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity);

        $this->assertEquals($expected, $serialized);
    }
}