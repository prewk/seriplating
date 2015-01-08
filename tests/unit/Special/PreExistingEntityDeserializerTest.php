<?php

namespace Prewk\Special;

use Prewk\Seriplating\Rule;
use Prewk\Seriplating\Seriplater;
use Prewk\Seriplating\Special\PreExistingEntityDeserializer;
use SeriplatingTestCase;
use Mockery;

class PreExistingEntityDeserializerTest extends SeriplatingTestCase
{
    public function test_deserialize()
    {
        $idResolver = Mockery::mock("Prewk\\Seriplating\\Contracts\\IdResolverInterface");
        $repository = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $t = new Seriplater(new Rule);

        $deserializer = new PreExistingEntityDeserializer($idResolver);

        $idResolver
            ->shouldReceive("bind")
            ->once()
            ->with("foos_0", 123);

        $deserializer->deserialize([
            "id" => $t->id("foos"),
        ], $repository, [
            "id" => 123,
            "_id" => "foos_0",
        ]);
    }
}