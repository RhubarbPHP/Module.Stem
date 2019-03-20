<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class CountTest extends MySqlTestCase
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
        $example->CompanyName = "b";
        $example->Balance = 2;
        $example->save();

        $example = new Company();
        $example->CompanyName = "c";
        $example->Balance = 3;
        $example->save();
    }

    public function testSumIsCalculatedOnRepository()
    {
        $examples = new RepositoryCollection("Company");

        list($sumTotal) = $examples->calculateAggregates(new Count("Balance"));

        $this->assertEquals(3, $sumTotal);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("COUNT(*) AS `CountOfBalance`", $lastStatement);
    }
}