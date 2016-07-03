<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Stem\Filters\DayOfWeek;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class DayOfWeekTest extends ModelUnitTestCase
{
    public function testFilter()
    {
        TestContact::clearObjectCache();

        $example = new TestContact();
        $example->DateOfBirth = new RhubarbDateTime("last monday");
        $example->save();

        $example = new TestContact();
        $example->DateOfBirth = new RhubarbDateTime("last tuesday");
        $example->save();

        $example = new TestContact();
        $example->DateOfBirth = new RhubarbDateTime("last sunday");
        $example->save();

        $example = new TestContact();
        $example->DateOfBirth = new RhubarbDateTime("last saturday");
        $example->save();

        $collection = TestContact::find(new DayOfWeek("DateOfBirth", [0, 1]));
        $this->assertCount(2, $collection);

        $this->assertEquals("Monday", $collection[0]->DateOfBirth->format("l"));
        $this->assertEquals("Tuesday", $collection[1]->DateOfBirth->format("l"));

        $collection = TestContact::find(new DayOfWeek("DateOfBirth", [0, 6]));
        $this->assertCount(2, $collection);

        $this->assertEquals("Sunday", $collection[1]->DateOfBirth->format("l"));
    }
}