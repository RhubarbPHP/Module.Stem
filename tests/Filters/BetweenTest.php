<?php


namespace Rhubarb\Stem\Tests\Filters;


use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Scaffolds\Login\User;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Between;

class BetweenTest extends RhubarbTestCase
{
    /**
     * @var Collection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->FavouriteNumber = 10;
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Mary";
        $example->FavouriteNumber = 15;
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->FavouriteNumber = 30;
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Jimmy";
        $example->Surname = "Joe";
        $example->FavouriteNumber = 5;
        $example->DateOfBirth = "1976-05-10";
        $example->save();

        $this->list = new Collection("\Rhubarb\Stem\Tests\Fixtures\Example");
    }

    public function testBetweenNumbers()
    {
        $this->list->filter(new Between("FavouriteNumber", 10, 20));

        $this->assertCount(2, $this->list);
        $this->assertEquals("John", $this->list[0]->Forename);
        $this->assertEquals("Mary", $this->list[1]->Forename);
    }
}
