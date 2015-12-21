<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;

class DecimalTest extends RhubarbTestCase
{
    public function testDataTransforms()
    {
        $example = new Example();

        // Test the min and max limits of the range
        $example->CreditLimit = 1000000000000000000;
        $example->Balance = -10000000000000000000;
        $this->assertEquals(99999999.99, $example->CreditLimit);
        $this->assertEquals(-9999.9999, $example->Balance);

        // Test rounding to the correct number of decimal places
        $example->CreditLimit = 3000.1256;
        $example->Balance = 3000.10002;
        $this->assertNotEquals(3000.1256, $example->CreditLimit);
        $this->assertEquals(3000.13, $example->CreditLimit);
        $this->assertNotEquals(3000.10002, $example->Balance);
        $this->assertEquals(3000.1, $example->Balance);
    }
}