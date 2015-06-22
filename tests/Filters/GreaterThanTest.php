<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 */
class GreaterThanTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
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
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Mary";
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $this->list = new Collection("\Rhubarb\Stem\Tests\Fixtures\Example");
    }

    public function testFiltersDate()
    {

        $filter = new \Rhubarb\Stem\Filters\GreaterThan("DateOfBirth", "1979-01-01");

        $this->list->filter($filter);
        $this->assertCount(2, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);
    }

    public function testFiltersAlpha()
    {

        $filter = new \Rhubarb\Stem\Filters\GreaterThan("Forename", "Mary", true);

        $this->list->filter($filter);
        $this->assertCount(2, $this->list);

        $filter = new \Rhubarb\Stem\Filters\GreaterThan("Forename", "Mary", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Tom", $this->list[0]->Forename);
    }
}