<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\SortNotValidException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class CollectionMySqlTest extends MySqlTestCase
{
    public function testBatchUpdate()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->Active = true;
        $company->save();

        $company = new Company();
        $company->CompanyName = "Unit Design";
        $company->Active = true;
        $company->save();

        $company = new Company();
        $company->CompanyName = "Goats Boats";
        $company->Active = true;
        $company->save();

        Company::find()->batchUpdate(["CompanyName" => "Test Company"]);

        $lastStatement = MySql::getPreviousStatement();

        $this->assertStringStartsWith("UPDATE `tblCompany` SET `CompanyName` = :UpdateCompanyName WHERE `tblCompany`.`Active` = ", $lastStatement);

        $count = MySql::returnSingleValue("SELECT COUNT(*) FROM tblCompany WHERE CompanyName = 'Test Company'");

        $this->assertEquals(3, $count);

        try {
            Company::find(new GreaterThan("CompanyIDSquared", 0,
                true))->batchUpdate(["CompanyName" => "Test Company 2"]);

            $this->fail("Batch update shouldn't have been allowed if not filtered by the repository.");
        } catch (BatchUpdateNotPossibleException $er) {
        }

        Company::find(new GreaterThan("CompanyIDSquared", 0,
            true))->batchUpdate(["CompanyName" => "Test Company 2"], true);

        $count = MySql::returnSingleValue("SELECT COUNT(*) FROM tblCompany WHERE CompanyName = 'Test Company 2'");

        $this->assertEquals(3, $count);
    }

    public function testDataListFetchesObjects()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $company = new Company();
        $company->CompanyName = "Unit Design";
        $company->save();

        $company = new Company();
        $company->CompanyName = "Goats Boats";
        $company->save();

        $list = new Collection(Company::class);

        $this->assertCount(3, $list);

        $repository = $company->getRepository();
        $repository->clearObjectCache();

        $list = new Collection(Company::class);

        $this->assertCount(3, $list);
        $this->assertEquals("Unit Design", $list[1]->CompanyName);

        $filter = new Equals("CompanyName", "Unit Design");
        $list = new Collection(Company::class);
        $list->filter($filter);

        $this->assertCount(1, $list);
        $this->assertEquals("Unit Design", $list[0]->CompanyName);

        $filter = new Equals("CompanyIDSquared", $company->CompanyID * $company->CompanyID);
        $list = new Collection(Company::class);
        $list->filter($filter);

        $this->assertCount(1, $list);
        $this->assertEquals("Goats Boats", $list[0]->CompanyName);
    }

    public function testListSorts()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $repos = $company->getRepository();
        $repos->clearObjectCache();

        $company = new Company();
        $company->CompanyName = "A";
        $company->Balance = 5;
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->Balance = 3;
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->Balance = 4;
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->Balance = 2;
        $company->save();

        $company = new Company();
        $company->CompanyName = "C";
        $company->Balance = 2;
        $company->save();

        $company = new Company();
        $company->CompanyName = "D";
        $company->Balance = 1;
        $company->save();

        $list = new Collection(Company::class);
        $list->addSort("CompanyName", true);

        // Trigger list fetching.
        sizeof($list);

        $sql = Mysql::getPreviousStatement();

        $this->assertContains("ORDER BY CompanyName ASC", $sql);

        $list->addSort("Balance", false);

        // Trigger list fetching.
        sizeof($list);

        $sql = Mysql::getPreviousStatement();

        $this->assertContains("ORDER BY CompanyName ASC, Balance DESC", $sql);

        // this should not affect our order by clause as this column isn't in our schema.
        $list->addSort("NonExistant", false);

        try {
            // Trigger list fetching.
            sizeof($list);
        } catch (SortNotValidException $er) {
        }

        $sql = Mysql::getPreviousStatement();

        // As NonExistant is at the end of the sort collection we can't use any back end performance
        // optimisation (as the manual sorting will destroy it)
        $this->assertNotContains("ORDER BY", $sql);

        $list->replaceSort(
            ["CompanyName" => false, "Balance" => true]
        );

        // Trigger list fetching.
        sizeof($list);

        $this->assertEquals("D", $list[0]->CompanyName);
        $this->assertEquals("C", $list[1]->CompanyName);
        $this->assertEquals("B", $list[2]->CompanyName);
        $this->assertEquals("B", $list[3]->CompanyName);
        $this->assertEquals("B", $list[4]->CompanyName);
        $this->assertEquals("A", $list[5]->CompanyName);

        $this->assertEquals(2, $list[2]->Balance);
        $this->assertEquals(3, $list[3]->Balance);
        $this->assertEquals(4, $list[4]->Balance);

        $list->replaceSort(
            ["CompanyName" => false, "CompanyIDSquared" => true, "Balance" => false]
        );

        // Trigger list fetching.
        sizeof($list);

        $this->assertEquals(3, $list[2]->Balance);
        $this->assertEquals(4, $list[3]->Balance);
        $this->assertEquals(2, $list[4]->Balance);
    }

    public function testLimits()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $repos = $company->getRepository();
        $repos->clearObjectCache();

        $company = new Company();
        $company->CompanyName = "A";
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->save();

        $company = new Company();
        $company->CompanyName = "B";
        $company->save();

        $company = new Company();
        $company->CompanyName = "C";
        $company->save();

        $company = new Company();
        $company->CompanyName = "D";
        $company->save();

        $list = new Collection(Company::class);
        $list->setRange(2, 6);

        $this->assertCount(6, $list);
        $this->assertEquals("C", $list[2]->CompanyName);
        $sql = MySql::getPreviousStatement(true);

        $this->assertContains("LIMIT 2, 6", $sql);

        // Sorting by a computed column should mean that limits are no longer used.
        $list->addSort("CompanyIDSquared", true);

        $this->assertCount(6, $list);
        $this->assertEquals("C", $list[2]->CompanyName);

        $sql = MySql::getPreviousStatement();
        $this->assertNotContains("LIMIT 2, 6", $sql);

        $sql = MySql::getPreviousStatement(true);
        $this->assertNotContains("LIMIT 2, 6", $sql);

    }
}
