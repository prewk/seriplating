<?php

require(__dir__ . "/../vendor/autoload.php");

class SeriplatingTestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}