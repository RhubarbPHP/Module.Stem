<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

/**
 * Tests the NOT filter.
 */
class NotTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new TestContact();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->Surname = "Joe";
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new TestContact();
        $example->Forename = "John";
        $example->Surname = "Johnson";
        $example->DateOfBirth = "1988-01-01";
        $example->save();

        $example = new TestContact();
        $example->Forename = "John";
        $example->Surname = "Luc";
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->Surname = "Smithe";
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new TestContact();
        $example->Forename = "James";
        $example->Surname = "Higgins";
        $example->DateOfBirth = "1996-05-09";
        $example->ContactID = 6;
        $example->save();

        $example = new TestContact();
        $example->Forename = "John";
        $example->Surname = "Higgins";
        $example->DateOfBirth = "1996-05-09";
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    function testFiltersSimple()
    {
        $notFilter = new Not(new Contains("Forename", "jo"));
        $this->list->filter($notFilter);
        $this->assertCount(3, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);
    }

    function testFiltersWithGroup()
    {
        $filterGroup = new Group("And");
        $filterGroup->addFilters(
            new Contains("Forename", "Jo", true),
            new Contains("Surname", "Johnson", true)
        );
        $notFilter = new Not($filterGroup);
        $this->list->filter($notFilter);
        $this->assertCount(6, $this->list);
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

        $notFilter = new Not($filterGroup);
        $this->list->filter($notFilter);
        $this->assertCount(3, $this->list);
        $this->assertContains("Smithe", $this->list[0]->Surname);
    }

    function testXOR()
    {
        $filterOne = new Contains("Forename", "Jo", true);
        $filterTwo = new Contains("Surname", "Jo", true);

        $filterAnd = new Group("And");
        $filterAnd->addFilters(
            $filterOne,
            $filterTwo
        );

        $filterOr = new Group("Or");
        $filterOr->addFilters(
            $filterOne,
            $filterTwo
        );

        $filterNotAnd = new Not($filterAnd);

        $filterXor = new Group("And");
        $filterXor->addFilters(
            $filterNotAnd,
            $filterOr
        );
        $this->list->filter($filterXor);
        $this->assertCount(2, $this->list);
        $this->assertContains("Luc", $this->list[0]->Surname);
    }

    function testNotFilteringAFilteredList()
    {
        $filter = new Contains("Forename", "Jo", true);
        $notFilter = new Not($filter);

        $listOne = new RepositoryCollection(TestContact::class);

        $this->assertCount(7, $listOne, "The list starts off with the size of 7");

        $this->list->filter($filter);
        $listOne->filter($notFilter);

        $this->assertCount(4, $this->list, "The list with the filter \$filter applied to it should be length 4");
        $this->assertCount(3, $listOne, "The list with the filter \$notFilter should be length 3, (7-4 = 3)");

        $listTwo = new RepositoryCollection(TestContact::class);
        $filterTwo = new Contains("Surname", "Thu", true);
        $listTwo->filter($filterTwo);

        $this->assertCount(1, $listTwo, "\$filterTwo applied to \$listTwo should make it's length 2");

        /*
        foreach($listOne as $x)
        {
            echo $x->Forename . " " . $x->Surname . "\n";
        }
        */

        $listOneArray = $listOne->toArray();

        foreach($listTwo as $ltItem)
        {
            if(in_array($ltItem, $listOneArray))
            {
                // echo "Trying to filter records where Surname is NOT equal to " . $ltItem->Surname . "\n";
                $listOne->filter(new Not(new Equals("Surname", $ltItem->Surname)));
            }
        }

        /*
        foreach ($listOne as $x) {
            echo $x->Forename . " " . $x->Surname . "\n";
        }
        */

        $this->assertCount(2, $listOne, "There should be two records");
        $this->assertEquals("Mary", $listOne[0]->Forename, "The first records forename is Mary");
    }
}