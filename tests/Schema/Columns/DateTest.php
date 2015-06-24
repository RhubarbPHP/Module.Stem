<?php

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Tests\Fixtures\Example;

class DateTest extends RhubarbTestCase
{
    public function testTransforms()
    {
        $example = new Example();
        $example->DateOfBirth = "2012-10-01";

        $rawData = $example->ExportRawData();

        $this->assertInstanceOf(\DateTime::class, $rawData["DateOfBirth"]);
        $this->assertEquals("1st October 2012", $rawData["DateOfBirth"]->format("jS F Y"));

        $dob = $example->DateOfBirth;

        $this->assertInstanceOf(\DateTime::class, $dob);
        $this->assertEquals("1st October 2012", $dob->format("jS F Y"));

        $example->DateOfBirth = "2012-10-02";

        $rawData = $example->ExportRawData();

        $this->assertInstanceOf(\DateTime::class, $rawData["DateOfBirth"]);
        $this->assertEquals("2nd October 2012", $rawData["DateOfBirth"]->format("jS F Y"));

        $dob = $example->DateOfBirth;

        $this->assertInstanceOf(\DateTime::class, $dob);
        $this->assertEquals("2nd October 2012", $dob->format("jS F Y"));

        $example->DateOfBirth = mktime(0, 0, 0, 10, 3, 2012);
        $this->assertEquals("3rd October 2012", $example->DateOfBirth->format("jS F Y"));

        $example->DateOfBirth = new \DateTime("2012-01-01");
        $this->assertEquals("1st January 2012", $example->DateOfBirth->format("jS F Y"));

        $example->DateOfBirth = "now";
        $this->assertEquals(date("jS F Y"), $example->DateOfBirth->format("jS F Y"));
    }

    public function testDateIsCloned()
    {
        $example = new Example();
        $example->DateOfBirth = "2012-10-01";

        $example->DateOfBirth->modify("+1 day");

        $this->assertEquals("2012-10-01", $example->DateOfBirth->format("Y-m-d"));
    }
}