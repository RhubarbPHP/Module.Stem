<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

/**
 * Data filter used to keep all records with a variable which is exactly equal to a particular variable.
 */
class EqualsTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        parent::setUp();

        $example = new TestContact();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    public function testFiltersMatchingRows()
    {
        $filter = new Equals("Forename", "Tom");

        $this->list->filter($filter);

        $this->assertCount(1, $this->list);
        $this->assertEquals("Thumb", $this->list[0]->Surname);
    }

    public function testSetFilterValue()
    {
        $filter = new Equals("CompanyID", 1);
        $model = new TestContact();

        $filter->setFilterValuesOnModel($model);

        $this->assertEquals(1, $model->CompanyID);
    }
}
