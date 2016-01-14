<?php

namespace Rhubarb\Stem\Tests\unit\Aggregates;

use Rhubarb\Stem\Aggregates\CountDistinct;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class CountDistinctTest extends ModelUnitTestCase
{
    public function testCount()
    {
        $company = new Company();
        $company->CompanyName = "a";
        $company->save();

        $company = new Company();
        $company->CompanyName = "b";
        $company->save();

        $company = new Company();
        $company->CompanyName = "b";
        $company->save();

        $company = new Company();
        $company->CompanyName = "a";
        $company->save();

        $collection = Company::find();

        list($companies) = $collection->calculateAggregates(new CountDistinct("CompanyName"));

        $this->assertEquals(2, $companies);
    }
}
