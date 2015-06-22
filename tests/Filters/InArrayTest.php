<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\InArray;

/**
 *
 * @author    cdoherty
 * @copyright GCD Technologies 2012
 */
class InArrayTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
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
        $example->ContactID = 1;
        $example->Forename = "John";
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->ContactID = 2;
        $example->Forename = "Mary";
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->ContactID = 3;
        $example->Forename = "Tom";
        $example->Surname = "Clancy";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->ContactID = 4;
        $example->Forename = "Clifford";
        $example->Surname = "Morris";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->ContactID = 5;
        $example->Forename = "Thomas";
        $example->Surname = "Harris";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->ContactID = 6;
        $example->Forename = "Martin";
        $example->Surname = "Sheen";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $this->list = new Collection("\Rhubarb\Stem\Tests\Fixtures\Example");
    }

    public function testContactIDInList()
    {
        $filter = new InArray("ContactID", [1, 2, 4]);
        $this->list->filter($filter);

        $this->assertCount(3, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);
        $this->assertContains("Mary", $this->list[1]->Forename);
        $this->assertContains("Clifford", $this->list[2]->Forename);
    }

    public function testContactForenameInList()
    {
        $filter = new InArray("Forename", ["Martin"]);
        $this->list->filter($filter);

        $this->assertCount(1, $this->list);
        $this->assertContains("Sheen", $this->list[0]->Surname);
        $this->assertEquals("6", $this->list[0]->ContactID);
    }

    public function testSurnameNotList()
    {
        $filter = new InArray("Surname", ["Elliott", "Sheen"]);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertEquals("6", $this->list[0]->ContactID);
    }

    public function testEmptyCandidatesArray()
    {
        $filter = new InArray("Surname", []);
        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }
}