<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;
use Mockery;

class GenericDeserializerTest extends SeriplatingTestCase
{
    private $seriplater;
    private $idResolver;
    private $repository;

    public function setUp()
    {
        $this->seriplater = new Seriplater(
            new Rule
        );

        $this->idResolver = Mockery::mock("Prewk\\Seriplating\\Contracts\\IdResolverInterface");
        $this->repository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
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
        $serialized = [
            "foo" => "bar",
            "baz" => "qux",
            "ipsum" => "asdf",
        ];
        $expected = [
            "foo" => "bar",
            "baz" => "qux",
            "ipsum" => "asdf",
        ];
        $id = 123;
        $expectedEntityData = [
            "id" => 123,
            "foo" => "bar",
            "baz" => "qux",
            "ipsum" => "asdf",
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_has_many()
    {
        $t = $this->seriplater;

        $template = [
            "foo" => $t->value(),
            "bar" => $t->hasMany("bars"),
        ];
        $serialized = [
            "foo" => "bar",
        ];
        $expected = [
            "foo" => "bar",
        ];
        $expectedEntityData = [
            "id" => 123,
            "foo" => "bar",
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_scalar_values()
    {
        $t = $this->seriplater;

        $template = [
            "foo" => $t->value(),
            "bar" => 123,
        ];
        $serialized = [
            "foo" => "bar",
        ];
        $expected = [
            "foo" => "bar",
            "bar" => 123,
        ];
        $expectedEntityData = [
            "id" => 123,
            "foo" => "bar",
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_inherits()
    {
        $t = $this->seriplater;

        $template = [
            "parent_id" => $t->inherits("id"),
            "foo_id" => $t->inherits("foo_id"),
            "bar" => $t->value(),
            "baz_id" => $t->inherits("baz_id", "foo_id"),
        ];
        $serialized = [
            "bar" => "baz",
        ];
        $expected = [
            "parent_id" => 456,
            "foo_id" => 789,
            "bar" => "baz",
            "baz_id" => 789,
        ];
        $expectedEntityData = [
            "id" => 123,
            "parent_id" => 456,
            "foo_id" => 789,
            "bar" => "baz",
            "baz_id" => 789,
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized, ["id" => 456, "foo_id" => 789]);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_increments()
    {
        $t = $this->seriplater;

        $template = [
            "bar" => $t->value(),
            "sort_order" => $t->increments(),
        ];
        $serialized = [
            "bar" => "baz",
        ];
        $expected = [
            "bar" => "baz",
            "sort_order" => 123,
        ];
        $expectedEntityData = [
            "id" => 123,
            "bar" => "baz",
            "sort_order" => 123,
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized, ["@sort_order" => 123]);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_id()
    {
        $t = $this->seriplater;

        $template = [
            "id" => $t->id("foos"),
            "bar" => $t->value(),
        ];
        $serialized = [
            "_id" => "foos_0",
            "bar" => "baz",
        ];
        $expected = [
            "bar" => "baz",
        ];
        $expectedEntityData = [
            "id" => 123,
            "bar" => "baz",
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $this->idResolver
            ->shouldReceive("bind")
            ->with("foos_0", 123);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
    }

    public function test_references()
    {
        $t = $this->seriplater;

        $template = [
            "foo_id" => $t->references("foos"),
            "bar_id" => $t->nullable()->references("foos"),
            "baz_id" => $t->nullable()->references("foos"),
        ];
        $serialized = [
            "foo_id" => ["_ref" => "foos_0"],
            "bar_id" => ["_ref" => "foos_0"],
            "baz_id" => null,
        ];
        $expected = [
            "foo_id" => 0,
            "bar_id" => 0,
            "baz_id" => null,
        ];
        $expectedEntityData = [
            "id" => 123,
            "foo_id" => 0,
            "bar_id" => 0,
            "baz_id" => null,
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $this->idResolver
            ->shouldReceive("defer")
            ->once()
            ->with("foos_0", $this->repository, 123, "foo_id", $expectedEntityData, null);

        $this->idResolver
            ->shouldReceive("defer")
            ->once()
            ->with("foos_0", $this->repository, 123, "bar_id", $expectedEntityData, null);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
        $this->assertNull($entityData["baz_id"]);
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
        $serialized1 = [
            "type" => "foo",
            "data" => "foo-value",
        ];
        $serialized2 = [
            "type" => "bar",
            "data" => ["_ref" => "bars_0"],
        ];
        $expected1 = [
            "type" => "foo",
            "data" => "foo-value",
        ];
        $expectedEntityData1 = [
            "id" => 123,
            "type" => "foo",
            "data" => "foo-value",
        ];
        $expected2 = [
            "type" => "bar",
            "data" => 0,
        ];
        $expectedEntityData2 = [
            "id" => 456,
            "type" => "bar",
            "data" => 0,
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected1)
            ->andReturn($expectedEntityData1);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized1);

        $this->assertEquals($expectedEntityData1, $entityData);

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected2)
            ->andReturn($expectedEntityData2);

        $this->idResolver
            ->shouldReceive("defer")
            ->once()
            ->with("bars_0", $this->repository, 456, "data", $expectedEntityData2, null);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized2);

        $this->assertEquals($expectedEntityData2, $entityData);
    }

    public function test_deserialize_deep()
    {
        $t = $this->seriplater;

        $template = [
            "data" => $t->deep([
                "/\\.blocks\\.\\d+.id$/" => $t->references("blocks"),
                "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
            ]),
        ];
        $serialized = [
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
        $expected = [
            "data" => [
                "rows" => [
                    [
                        "columns" => [
                            [
                                "blocks" => [
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                            [
                                "blocks" => [
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                        ],
                        "foo" => "bar",
                    ],
                ],
                "resources" => [
                    ["id" => 0, "transforms" => []],
                    ["id" => 0, "transforms" => []],
                ],
                "foo" => "baz",
            ],
        ];
        $expectedEntityData = [
            "id" => 123,
            "data" => [
                "rows" => [
                    [
                        "columns" => [
                            [
                                "blocks" => [
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                            [
                                "blocks" => [
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                        ],
                        "foo" => "bar",
                    ],
                ],
                "resources" => [
                    ["id" => 0, "transforms" => []],
                    ["id" => 0, "transforms" => []],
                ],
                "foo" => "baz",
            ],
        ];

        $this->idResolver
            ->shouldReceive("defer")
            ->once()
            ->with("blocks_0", $this->repository, 123, "data.rows.0.columns.0.blocks.0.id", $expectedEntityData, null)
            ->shouldReceive("defer")
            ->once()
            ->with("blocks_1", $this->repository, 123, "data.rows.0.columns.0.blocks.1.id", $expectedEntityData, null)
            ->shouldReceive("defer")
            ->once()
            ->with("blocks_2", $this->repository, 123, "data.rows.0.columns.1.blocks.0.id", $expectedEntityData, null)
            ->shouldReceive("defer")
            ->once()
            ->with("blocks_3", $this->repository, 123, "data.rows.0.columns.1.blocks.1.id", $expectedEntityData, null)

            ->shouldReceive("defer")
            ->once()
            ->with("resources_0", $this->repository, 123, "data.resources.0.id", $expectedEntityData, null)
            ->shouldReceive("defer")
            ->once()
            ->with("resources_1", $this->repository, 123, "data.resources.1.id", $expectedEntityData, null);

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
    }
}