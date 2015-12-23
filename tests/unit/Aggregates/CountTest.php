<?php

namespace Rhubarb\Stem\Tests\unit\Aggregates;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;

class CountTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Company::clearObjectCache();
    }

    public function testCount()
    {
        $company = new Company();
        $company->CompanyName = "a";
        $company->Active = true;
        $company->save();

        $company = new Company();
        $company->CompanyName = "b";
        $company->Active = true;
        $company->save();

        $company = new Company();
        $company->CompanyName = "c";
        $company->Active = true;
        $company->save();

        $company = new Company();
        $company->CompanyName = "d";
        $company->Active = true;
        $company->save();

        $collection = Company::find();

        list($companies) = $collection->calculateAggregates(new Count("CompanyName"));

        $this->assertEquals(4, $companies);
    }
}
