<?php

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Tests\Fixtures\Example;

class BooleanTest extends RhubarbTestCase
{
    public function testDataTransforms()
    {
        $example = new Example();
        $example->KeyContact = 1;

        $this->assertTrue($example->KeyContact);
    }
}
