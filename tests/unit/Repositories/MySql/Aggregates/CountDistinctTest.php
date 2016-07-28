<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\CountDistinct;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class CountDistinctTest extends MySqlTestCase
{
    protected function setUp()
    {
        parent::setUp();

        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $example = new Company();
        $example->getRepository()->clearObjectCache();

        $example = new Company();
        $example->CompanyName = "a";
        $example->Balance = 1;
        $example->save();

        $example = new Company();
        $example->CompanyName = "a";
        $example->Balance = 2;
        $example->save();

        $example = new Company();
        $example->CompanyName = "b";
        $example->Balance = 3;
        $example->save();

        $example = new Company();
        $example->CompanyName = "b";
        $example->Balance = 4;
        $example->save();
    }

    public function testSumIsCalculatedOnRepository()
    {
        $examples = new RepositoryCollection("Company");

        list($sumTotal) = $examples->calculateAggregates(new CountDistinct("CompanyName"));
        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertEquals(2, $sumTotal);

        $this->assertContains("COUNT( DISTINCT `", $lastStatement);
        $this->assertContains("DistinctCountOfCompanyName", $lastStatement);
    }
}