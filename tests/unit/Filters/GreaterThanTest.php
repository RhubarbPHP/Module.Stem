<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class GreaterThanTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new Example();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new Example();
        $example->Forename = "Mary";
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $this->list = new RepositoryCollection(Example::class);
    }

    public function testFiltersDate()
    {
        $filter = new GreaterThan("DateOfBirth", "1979-01-01");

        $this->list->filter($filter);
        $this->assertCount(2, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);
    }

    public function testFiltersAlpha()
    {
        $filter = new GreaterThan("Forename", "Mary", true);

        $this->list->filter($filter);
        $this->assertCount(2, $this->list);

        $filter = new GreaterThan("Forename", "Mary", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Tom", $this->list[0]->Forename);
    }
}