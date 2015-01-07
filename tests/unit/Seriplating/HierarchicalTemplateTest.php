<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;
use Mockery;

class HierarchicalTemplateMock extends HierarchicalTemplate
{
    public function getTemplateRegistry()
    {
        return $this->templateRegistry;
    }
}

class HierarchicalTemplateTest extends SeriplatingTestCase
{
    private $idResolver;

    public function setUp()
    {
        $this->idResolver = Mockery::mock("Prewk\\Seriplating\\Contracts\\IdResolverInterface");
    }

    public function test_that_registration_adds()
    {
        $hier = new HierarchicalTemplateMock($this->idResolver);
        $t = new Seriplater(
            new Rule
        );

        $template = [
            "id" => $t->id("foos"),
        ];

        $serializer = Mockery::mock("Prewk\\Seriplating\\Contracts\\BidirectionalTemplateInterface");
        $serializer
            ->shouldReceive("getTemplate")
            ->andReturn($template);

        $hier->register($serializer);

        $this->assertEquals(["foos" => $serializer], $hier->getTemplateRegistry());
    }

    /**
     * @expectedException \Prewk\Seriplating\Errors\HierarchicalCompositionException
     */
    public function test_that_missing_ids_fail()
    {
        $hier = new HierarchicalTemplate($this->idResolver);
        $t = new Seriplater(
            new Rule
        );

        $template = [
            "foo" => $t->value(),
        ];

        $serializer1 = Mockery::mock("Prewk\\Seriplating\\Contracts\\BidirectionalTemplateInterface");
        $serializer1
            ->shouldReceive("getTemplate")
            ->andReturn($template);

        $serializer2 = Mockery::mock("Prewk\\Seriplating\\Contracts\\BidirectionalTemplateInterface");
        $serializer2
            ->shouldReceive("getTemplate")
            ->andReturn($template);

        $hier->register($serializer1);
    }

    /**
     * @expectedException \Prewk\Seriplating\Errors\HierarchicalCompositionException
     */
    public function test_that_duplicate_registrations_fail()
    {
        $hier = new HierarchicalTemplate($this->idResolver);
        $t = new Seriplater(
            new Rule
        );

        $template = [
            "id" => $t->id("foos"),
        ];

        $serializer1 = Mockery::mock("Prewk\\Seriplating\\Contracts\\BidirectionalTemplateInterface");
        $serializer1
            ->shouldReceive("getTemplate")
            ->andReturn($template);

        $serializer2 = Mockery::mock("Prewk\\Seriplating\\Contracts\\BidirectionalTemplateInterface");
        $serializer2
            ->shouldReceive("getTemplate")
            ->andReturn($template);

        $hier->register($serializer1);
        $hier->register($serializer2);
    }
}