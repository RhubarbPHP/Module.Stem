<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;

class BooleanTest extends RhubarbTestCase
{
    public function testDataTransforms()
    {
        $example = new TestContact();
        $example->KeyContact = 1;

        $this->assertTrue($example->KeyContact);
    }
}
