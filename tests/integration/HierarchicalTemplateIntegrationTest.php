<?php

namespace Prewk;

use Prewk\Seriplating\GenericDeserializer;
use Prewk\Seriplating\GenericSerializer;
use Prewk\Seriplating\IdFactory;
use Prewk\Seriplating\IdResolver;
use Prewk\Seriplating\Seriplater;
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

        $topTemplate = new SeriplatingTemplate($serializer, $deserializer, [
            "id" => $t->id("tops"),
            "val" => $t->value(),
            "foos" => $t->hasMany("foos"),
            "bars" => $t->hasMany("bars"),
        ]);
        $fooTemplate = new SeriplatingTemplate($serializer, $deserializer, [
            "id" => $t->id("foos"),
            "top_id" => $t->inherits("id"),
            "val" => $t->value(),
        ]);
        $barTemplate = new SeriplatingTemplate($serializer, $deserializer, [
            "id" => $t->id("bars"),
            "val" => $t->value(),
            "bazes" => $t->hasMany("bazes"),
            "top_id" => $t->inherits("id"),
        ]);
        $bazTemplate = new SeriplatingTemplate($serializer, $deserializer, [
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
                        ["id" => 5, "val" => "baz", "top_id" => 1],
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
}