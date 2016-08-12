<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class CollectionTest extends ModelUnitTestCase
{
    public function testBatchUpdate()
    {
        $a = new Company();
        $a->CompanyName = "a";
        $a->Active = true;
        $a->save();

        $b = new Company();
        $b->CompanyName = "b";
        $b->Active = true;
        $b->save();

        $c = new Company();
        $c->CompanyName = "c";
        $c->Active = true;
        $c->save();

        $companies = Company::find()->batchUpdate(
            [
                "CompanyName" => "d"
            ]
        );

        foreach ($companies as $company) {
            $this->assertEquals("d", $company->CompanyName);
        }
    }

    public function testFindModelByUniqueIdentifier()
    {
        $a = new Company();
        $a->CompanyName = "a";
        $a->Active = true;
        $a->save();

        $b = new Company();
        $b->CompanyName = "b";
        $b->Active = true;
        $b->save();

        $c = new Company();
        $c->CompanyName = "c";
        $c->Active = true;
        $c->save();

        $companies = Company::all();
        $company = $companies->findModelByUniqueIdentifier(2);

        $this->assertEquals("b", $company->CompanyName);
    }

    public function testCalculateAggregateOnEmptyCollection()
    {
        Company::find()->deleteAll();
        $this->assertEquals([null], Company::find()->calculateAggregates([new Count('ComanyName')]));
    }
}
