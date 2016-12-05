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

    public function testRangeLimitedCursor()
    {
        $this->assertCount(0, Company::all());
        $company = new Company();
        $company->CompanyName = 'a';
        $company->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();
        (clone $company)->save();

        $this->assertCount(10, Company::all());

        $this->assertCount(10, Company::all()->setRange(0, 10));
        $this->assertCount(10, Company::all()->setRange(0, 15));
        $this->assertEquals(1, Company::all()[0]->getUniqueIdentifier());
        $id = 2;
        foreach(Company::all()->setRange(1, 10) as $company) {
            $this->assertEquals($id++, $company->getUniqueIdentifier(), 'limited ranges must be iterable');
        }
        $this->assertEquals(
            2,
            Company::all()->setRange(1, 10)[0]->getUniqueIdentifier(),
            'direct access of ranges should start from the right place'
        );
        $this->assertCount(9, Company::all()->setRange(1, 10));
        $this->assertCount(2, Company::all()->setRange(8, 2));
        $this->assertCount(2, Company::all()->setRange(8, 15));
    }
}
