<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class SumTest extends MySqlTestCase
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

        list($sumTotal) = $examples->calculateAggregates(new Sum("Balance"));

        $this->assertEquals(6, $sumTotal);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("SUM( `", $lastStatement);
        $this->assertContains("`SumOfBalance`", $lastStatement);

        $examples = new RepositoryCollection("Company");
        $examples->filter(new GreaterThan("Balance", 1));

        list($sumTotal) = $examples->calculateAggregates(new Sum("Balance"));

        $this->assertEquals(5, $sumTotal);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("SUM( `", $lastStatement);
        $this->assertContains("`SumOfBalance`", $lastStatement);
        $this->assertContains("`Balance` > ", $lastStatement);
    }
}