<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\Min;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Models\Validation\LessThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class MySqlMinTest extends MySqlTestCase
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

    public function testMinIsCalculatedOnRepository()
    {
        $examples = new RepositoryCollection("Company");

        list($min) = $examples->calculateAggregates(new Min("Balance"));

        $this->assertEquals(1, $min);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("MIN( `", $lastStatement);
        $this->assertContains("`MinOfBalance`", $lastStatement);

        $examples = new RepositoryCollection("Company");
        $examples->filter(new GreaterThan("Balance", 1));

        list($min) = $examples->calculateAggregates(new Min("Balance"));

        $this->assertEquals(2, $min);

        $lastStatement = MySql::getPreviousStatement(false);

        $this->assertContains("MIN( `", $lastStatement);
        $this->assertContains("`MinOfBalance`", $lastStatement);
        $this->assertContains("`Balance` > ", $lastStatement);
    }
}