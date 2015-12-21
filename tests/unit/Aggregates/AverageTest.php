<?php

namespace Rhubarb\Stem\Tests\unit\Aggregates;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Aggregates\Average;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class AverageTest extends RhubarbTestCase
{
    public function testAverage()
    {
        $user = new User();
        $user->Wage = 100;
        $user->Active = true;
        $user->save();

        $user = new User();
        $user->Wage = 200;
        $user->Active = true;
        $user->save();

        $user = new User();
        $user->Wage = 600;
        $user->Active = true;
        $user->save();

        $collection = User::find();

        list($average) = $collection->calculateAggregates([new Average("Wage")]);

        $this->assertEquals(300, $average);
    }
}
