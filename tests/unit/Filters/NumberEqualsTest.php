<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\NumberEquals;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

/**
 * Data filter used to keep all records with a variable which is exactly equal to a particular variable.
 */
class NumberEqualsTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        parent::setUp();

        $example = new Company();
        $example->getRepository()->clearObjectCache();
        $example->CompanyName = "1";
        $example->Balance = 10;
        $example->save();

        $example = new Company();
        $example->CompanyName = "2";
        $example->Balance = 10;
        $example->save();

        $example = new Company();
        $example->CompanyName = "3";
        $example->Balance = 20;
        $example->save();

        $example = new Company();
        $example->CompanyName = "3";
        $example->Balance = 12345.67;
        $example->save();

        $this->list = Company::find();
    }

    public function testFiltersEngagedWhenANumber()
    {
        $filter = new NumberEquals("Balance", "Tom");
        $this->list->filter($filter);

        $this->assertCount(4, $this->list);

        $filter = new NumberEquals("Balance", 10);
        $this->list->replaceFilter($filter);

        $this->assertCount(2, $this->list);

        $filter = new NumberEquals("Balance", "20.00");
        $this->list->replaceFilter($filter);

        $this->assertCount(1, $this->list);

        $filter = new NumberEquals("Balance", "12,345.67");
        $this->list->replaceFilter($filter);

        $this->assertCount(1, $this->list);
    }

    public function testSetFilterValue()
    {
        $filter = new Equals("CompanyID", 1);
        $model = new Example();

        $filter->setFilterValuesOnModel($model);

        $this->assertEquals(1, $model->CompanyID);
    }
}
