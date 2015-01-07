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
        $id = 123;
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
        ];
        $serialized = [
            "foo_id" => ["_ref" => "foos_0"],
        ];
        $expected = [
            "foo_id" => 0,
        ];
        $expectedEntityData = [
            "id" => 123,
            "foo" => 0,
        ];

        $this->repository
            ->shouldReceive("create")
            ->once()
            ->with($expected)
            ->andReturn($expectedEntityData);

        $this->idResolver
            ->shouldReceive("deferResolution")
            ->once()
            ->with("foos_0", Mockery::any());

        $deser = new GenericDeserializer($this->idResolver);
        $entityData = $deser->deserialize($template, $this->repository, $serialized);

        $this->assertEquals($expectedEntityData, $entityData);
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
            ->shouldReceive("deferResolution")
            ->once()
            ->with("bars_0", Mockery::any());

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
            ->shouldReceive("deferResolution")
            ->once()
            ->with("blocks_0", Mockery::any())
            ->shouldReceive("deferResolution")
            ->once()
            ->with("blocks_1", Mockery::any())
            ->shouldReceive("deferResolution")
            ->once()
            ->with("blocks_2", Mockery::any())
            ->shouldReceive("deferResolution")
            ->once()
            ->with("blocks_3", Mockery::any())

            ->shouldReceive("deferResolution")
            ->once()
            ->with("resources_0", Mockery::any())
            ->shouldReceive("deferResolution")
            ->once()
            ->with("resources_1", Mockery::any());

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