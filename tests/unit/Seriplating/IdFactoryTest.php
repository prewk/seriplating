<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;

class IdFactoryTest extends SeriplatingTestCase
{
    public function test_get()
    {
        $idFactory = new IdFactory;

        $foos1 = $idFactory->get("foos", 1);
        $foos2 = $idFactory->get("foos", 2);
        $foos1b = $idFactory->get("foos", 1);
        $foos2b = $idFactory->get("foos", 2);

        $this->assertNotEquals($foos1, $foos2);
        $this->assertEquals($foos1, $foos1b);
        $this->assertEquals($foos2, $foos2b);
    }
}