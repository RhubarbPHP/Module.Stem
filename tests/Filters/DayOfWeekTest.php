<?php


namespace Rhubarb\Stem\Tests\Filters;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Tests\Fixtures\Example;


class DayOfWeekTest extends RhubarbTestCase
{
    public function testFilter()
    {
        Example::clearObjectCache();

        $example = new Example();
        $example->DateOfBirth = new RhubarbDateTime("last monday");
        $example->save();

        $example = new Example();
        $example->DateOfBirth = new RhubarbDateTime("last tuesday");
        $example->save();

        $example = new Example();
        $example->DateOfBirth = new RhubarbDateTime("last sunday");
        $example->save();

        $example = new Example();
        $example->DateOfBirth = new RhubarbDateTime("last saturday");
        $example->save();

        $collection = Example::find(new DayOfWeek("DateOfBirth", [0, 1]));
        $this->assertCount(2, $collection);

        $this->assertEquals("Monday", $collection[0]->DateOfBirth->format("l"));
        $this->assertEquals("Tuesday", $collection[1]->DateOfBirth->format("l"));

        $collection = Example::find(new DayOfWeek("DateOfBirth", [0, 6]));
        $this->assertCount(2, $collection);

        $this->assertEquals("Sunday", $collection[1]->DateOfBirth->format("l"));
    }
}
