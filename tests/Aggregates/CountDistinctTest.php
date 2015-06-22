<?php

namespace Rhubarb\Stem\Tests\Aggregates;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Aggregates\CountDistinct;
use Rhubarb\Stem\Tests\Fixtures\Company;

class CountDistinctTest extends RhubarbTestCase
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
