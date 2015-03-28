<?php

namespace Prewk\Seriplating\BidirectionalTemplates;

use Prewk\Seriplating\IdFactory;
use Prewk\Seriplating\IdResolver;
use Prewk\Seriplating\Rule;
use Prewk\Seriplating\Seriplater;
use SeriplatingTestCase;

class ContainerTemplateIntegrationTest extends SeriplatingTestCase
{
    public function test_serialization()
    {
        $idFactory = new IdFactory();
        $idResolver = new IdResolver();
        $t = new Seriplater(new Rule());

        $containerTemplate = new ContainerTemplate($idFactory, $idResolver, [
            "id" => $t->id("foos"),
        ]);

        $serialized = $containerTemplate->serialize(["id" => 123]);

        $this->assertEquals("foos_0", $serialized["_id"]);
    }

    public function test_deserialization()
    {
        $idFactory = new IdFactory();
        $idResolver = new IdResolver();
        $t = new Seriplater(new Rule());

        $containerTemplate = new ContainerTemplate($idFactory, $idResolver, [
            "id" => $t->id("foos"),
        ], 123);

        $deserialized = $containerTemplate->deserialize(["_id" => "foos_0"]);


        $this->assertEquals("123", $deserialized["id"]);
    }
}