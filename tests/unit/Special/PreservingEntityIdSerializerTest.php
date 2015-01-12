<?php

namespace Prewk\Special;

use Mockery;
use Prewk\Seriplating\Rule;
use Prewk\Seriplating\Seriplater;
use Prewk\Seriplating\Special\PreservingEntityIdSerializer;
use SeriplatingTestCase;

class PreservingEntityIdSerializerTest extends SeriplatingTestCase
{
    public function test_serializer()
    {
        $idFactory = Mockery::mock("Prewk\\Seriplating\\Contracts\\IdFactoryInterface");
        $serializer = new PreservingEntityIdSerializer($idFactory);
        $t = new Seriplater(new Rule);

        $idFactory
            ->shouldReceive("get")
            ->once()
            ->with("resources", 123)
            ->andReturn("resources_0");

        $serialized = $serializer->serialize([
            "id" => $t->id("resources"),
        ], [
            "id" => 123,
        ]);

        $expected = [
            "id" => 123,
            "_id" => "resources_0",
        ];

        $this->assertEquals($expected, $serialized);
    }
}