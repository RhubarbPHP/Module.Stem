<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\Max;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\GreaterThan;

use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class MySqlMaxTest extends MySqlTestCase
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

    public function testMaxIsCalculatedOnRepository()
    {
        $examples = new RepositoryCollection("Company");

        list($max) = $examples->calculateAggregates(new Max("Balance"));

        $this->assertEquals(3, $max);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("MAX( `", $lastStatement);
        $this->assertContains("`MaxOfBalance`", $lastStatement);

        $examples = new RepositoryCollection("Company");
        $examples->filter(new LessThan("Balance", 3));

        list($max) = $examples->calculateAggregates(new Max("Balance"));

        $this->assertEquals(2, $max);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("MAX( `", $lastStatement);
        $this->assertContains("`MaxOfBalance`", $lastStatement);
        $this->assertContains("`Balance` < ", $lastStatement);
    }
}