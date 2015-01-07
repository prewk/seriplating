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
}