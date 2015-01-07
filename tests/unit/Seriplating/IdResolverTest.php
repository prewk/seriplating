<?php

namespace Prewk\Seriplating;

use SeriplatingTestCase;

class IdResolverTest extends SeriplatingTestCase
{
    public function test_binding_deferring_and_resolving()
    {
        $resolver = new IdResolver;

        $foo0 = null;
        $foo1 = null;

        $resolver->bind("foos_0", 1);
        $resolver->deferResolution("foos_1", function($dbId) use (&$foo1) {
            $foo1 = $dbId;
        });
        $resolver->bind("foos_1", 2);
        $resolver->deferResolution("foos_0", function($dbId) use (&$foo0) {
            $foo0 = $dbId;
        });

        $resolver->resolve();

        $this->assertEquals($foo0, 1);
        $this->assertEquals($foo1, 2);
    }

    public function test_internal_id_arrays()
    {
        $resolver = new IdResolver;

        $foo0 = null;
        $shouldBeNull = "not null";
        $foo1 = null;
        $foo2 = null;

        $resolver->bind("foos_0", 1);
        $resolver->bind("foos_1", 2);
        $resolver->bind("foos_2", 3);

        $resolver->deferResolution(["foos_0", null, "foos_1", "foos_2"], function($dbId1, $isNull, $dbId2, $dbId3) use (&$foo0, &$shouldBeNull, &$foo1, &$foo2) {
            $foo0 = $dbId1;
            $shouldBeNull = $isNull;
            $foo1 = $dbId2;
            $foo2 = $dbId3;
        });

        $resolver->resolve();

        $this->assertEquals(1, $foo0);
        $this->assertEquals(null, $shouldBeNull);
        $this->assertEquals(2, $foo1);
        $this->assertEquals(3, $foo2);
    }
}