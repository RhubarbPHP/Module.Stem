<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class GroupTest extends ModelUnitTestCase
{
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
        $example->ContactID = 1;
        $example->save();

        $example = new TestContact();
        $example->Forename = "John";
        $example->Surname = "Johnson";
        $example->DateOfBirth = "1988-01-01";
        $example->ContactID = 2;
        $example->save();

        $example = new TestContact();
        $example->Forename = "John";
        $example->Surname = "Luc";
        $example->DateOfBirth = "1990-01-01";
        $example->ContactID = 3;
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->Surname = "Smithe";
        $example->DateOfBirth = "1980-06-09";
        $example->ContactID = 4;
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->DateOfBirth = "1976-05-09";
        $example->ContactID = 5;
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    public function testFiltersAnd()
    {
        $filterGroup = new Group("And");
        $filterGroup->addFilters(
            new Contains("Forename", "Jo", true),
            new Contains("Surname", "Johnson", true)
        );
        $this->list->filter($filterGroup);
        $this->assertCount(1, $this->list);
        $this->assertContains("Johnson", $this->list[0]->Surname);
    }

    public function testFiltersOr()
    {
        $filterGroup = new Group("Or");
        $filterGroup->addFilters(
            new Contains("Forename", "Jo", true),
            new Contains("Surname", "Smithe", true)
        );
        $this->list->filter($filterGroup);
        $this->assertCount(4, $this->list);
        $this->assertContains("Smithe", $this->list[3]->Surname);
    }

    //filter Group with a group inside for recursive banter
    public function testFiltersGrouped()
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
        $this->list->filter($filterGroup);
        $this->assertCount(4, $this->list);
        $this->assertContains("Joe", $this->list[0]->Surname);
    }

    public function testFilterSetsModelValues()
    {
        $subGroup = new Group("And");
        $subGroup->addFilters(
            new Equals("Forename", "Andrew"),
            new GreaterThan("DateOfBirth", 18)
        );

        $andGroup = new Group("And");
        $andGroup->addFilters(
            new Equals("CompanyID", 1),
            new Equals("Surname", "Cuthbert"),
            $subGroup
        );

        $orGroup = new Group("Or");
        $orGroup->addFilters(
            new Equals("CompanyID", 1),
            new Equals("Surname", "Cuthbert"),
            $subGroup
        );

        $model = new TestContact();
        $andGroup->setFilterValuesOnModel($model);

        $this->assertEquals(1, $model->CompanyID);
        $this->assertEquals("Cuthbert", $model->Surname);
        $this->assertEquals("Andrew", $model->Forename);

        $model = new TestContact();
        $orGroup->setFilterValuesOnModel($model);

        $this->assertNotEquals(1, $model->CompanyID);
        $this->assertNotEquals("Cuthbert", $model->Surname);
        $this->assertNotEquals("Andrew", $model->Forename);
    }

    public function testAndOrGroupsParametersNotAsArray()
    {
        $andGroup = new AndGroup(new Equals("Forename", "John"), new Equals("Surname", "Luc"));
        $contacts = TestContact::find($andGroup);
        $model = $contacts[0];
        $this->assertEquals("John", $model->Forename);
        $this->assertEquals("Luc", $model->Surname);

        $orGroup = new OrGroup(new Equals("Forename", "John"), new Equals("Surname", "Luc"));
        $contacts = TestContact::find($orGroup);
        $model = $contacts[0];
        $this->assertEquals("John", $model->Forename);
        $this->assertEquals("Joe", $model->Surname);

        TestContact::find(new Equals("Forename", "John"), new Equals("Surname", "Luc"));
    }

    public function testFindAutomaticAndFilterForSeveralParameters()
    {
        $contacts = TestContact::find(new Equals("Forename", "John"), new Equals("Surname", "Luc"));
        $model = $contacts[0];
        $this->assertEquals("John", $model->Forename);
        $this->assertEquals("Luc", $model->Surname);
    }

    public function testFilterAutomaticAndFilterForSeveralParameters()
    {
        $contacts = $this->list->filter(
            new Contains("Forename", "Joh", true),
            new Contains("Surname", "Joh", true)
        );
        $model = $contacts[0];
        $this->assertEquals("John", $model->Forename);
        $this->assertEquals("Johnson", $model->Surname);

        $contacts2 = $this->list->filter([
            new Contains("Forename", "Joh", true),
            new Contains("Surname", "Joh", true)
        ]);
        $model = $contacts2[0];
        $this->assertEquals("John", $model->Forename);
        $this->assertEquals("Johnson", $model->Surname);
    }
}
