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
    public function test_registration()
    {
        $hier = new HierarchicalTemplateMock;
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
}