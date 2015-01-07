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
}