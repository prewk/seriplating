<?php

namespace Prewk\Seriplating;

use Mockery;
use Prewk\SeriplatingTemplate;
use SeriplatingTestCase;

class HierarchicalTemplateIntegrationTest extends SeriplatingTestCase
{
    public function test_hierarchical_serialization()
    {
        $t = new Seriplater(new Rule);
        $idResolver = new IdResolver;
        $idFactory = new IdFactory;
        $hier = new HierarchicalTemplate($idResolver);
        $serializer = new GenericSerializer($idFactory);
        $deserializer = new GenericDeserializer($idResolver);
        $repository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");

        $topTemplate = new SeriplatingTemplate($serializer, $deserializer, $repository, [
            "id" => $t->id("tops"),
            "val" => $t->value(),
            "foos" => $t->hasMany("foos"),
            "bars" => $t->hasMany("bars"),
        ]);
        $fooTemplate = new SeriplatingTemplate($serializer, $deserializer, $repository, [
            "id" => $t->id("foos"),
            "top_id" => $t->inherits("id"),
            "val" => $t->value(),
        ]);
        $barTemplate = new SeriplatingTemplate($serializer, $deserializer, $repository, [
            "id" => $t->id("bars"),
            "val" => $t->value(),
            "bazes" => $t->hasMany("bazes"),
            "top_id" => $t->inherits("id"),
        ]);
        $bazTemplate = new SeriplatingTemplate($serializer, $deserializer, $repository, [
            "id" => $t->id("bazes"),
            "val" => $t->value(),
            "bar_id" => $t->inherits("id"),
            "top_id" => $t->inherits("top_id"),
        ]);

        $data = [
            "id" => 1,
            "val" => "lorem",
            "foos" => [
                ["id" => 2, "val" => "ipsum", "top_id" => 1],
                ["id" => 3, "val" => "foo", "top_id" => 1],
            ],
            "bars" => [
                [
                    "id" => 4,
                    "val" => "bar",
                    "bazes" => [
                        ["id" => 5, "val" => "baz", "top_id" => 1, "bar_id" => 4],
                    ],
                    "top_id" => 1,
                ]
            ],
        ];

        $expected = [
            "_id" => $idFactory->get("tops", 1),
            "val" => "lorem",
            "foos" => [
                ["_id" => $idFactory->get("foos", 2), "val" => "ipsum"],
                ["_id" => $idFactory->get("foos", 3), "val" => "foo"],
            ],
            "bars" => [
                [
                    "_id" => $idFactory->get("bars", 4),
                    "val" => "bar",
                    "bazes" => [
                        ["_id" => $idFactory->get("bazes", 5), "val" => "baz"],
                    ],
                ]
            ],
        ];

        $hier
            ->register($topTemplate)
            ->register($fooTemplate)
            ->register($barTemplate)
            ->register($bazTemplate);

        $serialization = $hier->serialize("tops", $data);

        $this->assertEquals($expected, $serialization);
    }

    public function test_hierarchical_deserialization()
    {
        $t = new Seriplater(new Rule);
        $idResolver = new IdResolver;
        $idFactory = new IdFactory;
        $hier = new HierarchicalTemplate($idResolver);
        $serializer = new GenericSerializer($idFactory);
        $deserializer = new GenericDeserializer($idResolver);

        $topRepository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $topTemplate = new SeriplatingTemplate($serializer, $deserializer, $topRepository, [
            "id" => $t->id("tops"),
            "val" => $t->value(),
            "foos" => $t->hasMany("foos"),
            "bars" => $t->hasMany("bars"),
        ]);

        $fooRepository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $fooTemplate = new SeriplatingTemplate($serializer, $deserializer, $fooRepository, [
            "id" => $t->id("foos"),
            "top_id" => $t->inherits("id"),
            "val" => $t->value(),
        ]);

        $barRepository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $barTemplate = new SeriplatingTemplate($serializer, $deserializer, $barRepository, [
            "id" => $t->id("bars"),
            "val" => $t->value(),
            "bazes" => $t->hasMany("bazes"),
            "top_id" => $t->conditions("val", [
                "bar" => $t->inherits("id"),
            ]),
        ]);

        $bazRepository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $bazTemplate = new SeriplatingTemplate($serializer, $deserializer, $bazRepository, [
            "id" => $t->id("bazes"),
            "val" => $t->value(),
            "bar_id" => $t->inherits("id"),
            "top_id" => $t->inherits("top_id"),
            "foo_id" => $t->references("foos"),
        ]);

        $serialization = [
            "_id" => $idFactory->get("tops", 1),
            "val" => "lorem",
            "foos" => [
                ["_id" => $idFactory->get("foos", 2), "val" => "ipsum"],
                ["_id" => $idFactory->get("foos", 3), "val" => "foo"],
            ],
            "bars" => [
                [
                    "_id" => $idFactory->get("bars", 4),
                    "val" => "bar",
                    "bazes" => [
                        ["_id" => $idFactory->get("bazes", 5), "val" => "baz", "foo_id" => ["_ref" => $idFactory->get("foos", 2)]],
                    ],
                ]
            ],
        ];

        $expectedCreatedEntityData = [
            "id" => 1,
            "val" => "lorem",
            "foos" => [
                ["id" => 2, "val" => "ipsum", "top_id" => 1],
                ["id" => 3, "val" => "foo", "top_id" => 1],
            ],
            "bars" => [
                [
                    "id" => 4,
                    "val" => "bar",
                    "bazes" => [
                        ["id" => 5, "val" => "baz", "top_id" => 1, "bar_id" => 4, "foo_id" => 0],
                    ],
                    "top_id" => 1,
                ]
            ],
        ];

        $topRepository
            ->shouldReceive("create")
            ->once()
            ->with([
                "val" => "lorem",
            ])
            ->andReturn([
                "id" => 1,
                "val" => "lorem",
            ]);

        $fooRepository
            ->shouldReceive("create")
            ->once()
            ->with([
                "val" => "ipsum",
                "top_id" => 1,
            ])
            ->andReturn([
                "id" => 2,
                "val" => "ipsum",
                "top_id" => 1,
            ])
            ->shouldReceive("create")
            ->once()
            ->with([
                "val" => "foo",
                "top_id" => 1,
            ])
            ->andReturn([
                "id" => 3,
                "val" => "foo",
                "top_id" => 1,
            ]);

        $barRepository
            ->shouldReceive("create")
            ->once()
            ->with([
                "val" => "bar",
                "top_id" => 1,
            ])
            ->andReturn([
                "id" => 4,
                "val" => "bar",
                "top_id" => 1,
            ]);

        $bazRepository
            ->shouldReceive("create")
            ->once()
            ->with([
                "val" => "baz",
                "top_id" => 1,
                "bar_id" => 4,
                "foo_id" => 0,
            ])
            ->andReturn([
                "id" => 5,
                "val" => "baz",
                "top_id" => 1,
                "bar_id" => 4,
                "foo_id" => 0,
            ])
            ->shouldReceive("update")
            ->once()
            ->with(5, [
                "foo_id" => 2
            ]);

        $hier
            ->register($topTemplate)
            ->register($fooTemplate)
            ->register($barTemplate)
            ->register($bazTemplate);

        $entityData = $hier->deserialize("tops", $serialization);

        $this->assertEquals($expectedCreatedEntityData, $entityData);
   }
}