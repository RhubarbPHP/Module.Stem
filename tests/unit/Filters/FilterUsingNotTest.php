<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

/**
 * Tests the NOT filter.
 */
class FilterUsingNotTest extends ModelUnitTestCase
{
    /**
     * @var Collection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new Example();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->Surname = "Joe";
        $example->DateOfBirth = "1990-01-01";
        $example->ContactID = 1;
        $example->save();

        $example->Forename = "John";
        $example->Surname = "Johnson";
        $example->DateOfBirth = "1988-01-01";
        $example->ContactID = 2;
        $example->save();

        $example->Forename = "John";
        $example->Surname = "Luc";
        $example->DateOfBirth = "1990-01-01";
        $example->ContactID = 3;
        $example->save();

        $example = new Example();
        $example->Forename = "Mary";
        $example->Surname = "Smithe";
        $example->DateOfBirth = "1980-06-09";
        $example->ContactID = 4;
        $example->save();

        $example = new Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->DateOfBirth = "1976-05-09";
        $example->ContactID = 5;
        $example->save();

        $this->list = new Collection(Example::class);
    }

    function testFiltersSimple()
    {
        $filter = new Contains("Forename", "jo");
        $this->list->Not($filter);
        $this->assertCount(2, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);
    }

    function testFiltersWithGroup()
    {
        $filterGroup = new Group("And");
        $filterGroup->addFilters(
            new Contains("Forename", "Jo", true),
            new Contains("Surname", "Johnson", true)
        );
        $this->list->Not($filterGroup);
        $this->assertCount(4, $this->list);
        $this->assertContains("Joe", $this->list[0]->Surname);
    }

    function testFiltersWithGroupedGroup()
    {
        $filterGroup1 = new Group("And");
        $filterGroup1->addFilters(
            new Contains("Forename", "Jo", true),
            new Contains("Surname", "Jo", true)
        );

        $filterGroup2 = new Group("Or");
        $filterGroup2->addFilters(
            new Contains("Surname", "Luc", true),
            new LessThan("DateOfBirth", "1980-01-01", true)
        );

        $filterGroup = new Group("Or");
        $filterGroup->addFilters(
            $filterGroup1,
            $filterGroup2
        );

        $this->list->Not($filterGroup);
        $this->assertCount(1, $this->list);
        $this->assertContains("Smithe", $this->list[0]->Surname);
    }
}