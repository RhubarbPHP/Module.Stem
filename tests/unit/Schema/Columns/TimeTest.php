<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;

class TimeTest extends RhubarbTestCase
{
    public function testTransforms()
    {
        $example = new Example();
        $example->CoffeeTime = "11:00";

        $this->assertEquals("2000-01-01 11:00:00", $example->CoffeeTime->format("Y-m-d H:i:s"));
    }
}
