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

    public function test_has_many()
    {
        $t = $this->seriplater;

        $template = [
            "foo" => $t->value(),
            "bar" => $t->hasMany("bars"),
        ];
        $entity = [
            "foo" => "bar",
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

    public function test_conditions()
    {
        $t = $this->seriplater;

        $template = [
            "type" => $t->value(),
            "data" => $t->conditions("type", [
                "foo" => $t->value(),
                "bar" => $t->references("bars"),
            ]),
        ];
        $entity1 = [
            "type" => "foo",
            "data" => "foo-value",
        ];
        $entity2 = [
            "type" => "bar",
            "data" => 123,
        ];
        $expected1 = [
            "type" => "foo",
            "data" => "foo-value",
        ];
        $expected2 = [
            "type" => "bar",
            "data" => ["_ref" => "bars_0"],
        ];

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity1);

        $this->assertEquals($expected1, $serialized);

        $this->idFactory
            ->shouldReceive("get")
            ->with("bars", 123)
            ->andReturn("bars_0");

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity2);

        $this->assertEquals($expected2, $serialized);
    }

    public function test_deep()
    {
        $t = $this->seriplater;

        $template = [
            "data" => $t->deep([
                "/\\.blocks\\.\\d+.id$/" => $t->references("blocks"),
                "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
            ]),
        ];
        $entity = [
            "data" => [
                "rows" => [
                    [
                        "columns" => [
                            [
                                "blocks" => [
                                    ["id" => 1],
                                    ["id" => 2],
                                ],
                            ],
                            [
                                "blocks" => [
                                    ["id" => 3],
                                    ["id" => 4],
                                ],
                            ],
                        ],
                        "foo" => "bar",
                    ],
                ],
                "resources" => [
                    ["id" => 5, "transforms" => []],
                    ["id" => 6, "transforms" => []],
                ],
                "foo" => "baz",
            ],
        ];
        $expected = [
            "data" => [
                "rows" => [
                    [
                        "columns" => [
                            [
                                "blocks" => [
                                    ["id" => ["_ref" => "blocks_0"]],
                                    ["id" => ["_ref" => "blocks_1"]],
                                ],
                            ],
                            [
                                "blocks" => [
                                    ["id" => ["_ref" => "blocks_2"]],
                                    ["id" => ["_ref" => "blocks_3"]],
                                ],
                            ],
                        ],
                        "foo" => "bar",
                    ],
                ],
                "resources" => [
                    ["id" => ["_ref" => "resources_0"], "transforms" => []],
                    ["id" => ["_ref" => "resources_1"], "transforms" => []],
                ],
                "foo" => "baz",
            ],
        ];

        $this->idFactory
            ->shouldReceive("get")
            ->with("blocks", 1)
            ->andReturn("blocks_0")
            ->shouldReceive("get")
            ->with("blocks", 2)
            ->andReturn("blocks_1")
            ->shouldReceive("get")
            ->with("blocks", 3)
            ->andReturn("blocks_2")
            ->shouldReceive("get")
            ->with("blocks", 4)
            ->andReturn("blocks_3")

            ->shouldReceive("get")
            ->with("resources", 5)
            ->andReturn("resources_0")
            ->shouldReceive("get")
            ->with("resources", 6)
            ->andReturn("resources_1");

        $ser = new GenericSerializer($this->idFactory);
        $serialized = $ser->serialize($template, $entity);

        $this->assertEquals($expected, $serialized);
    }
}