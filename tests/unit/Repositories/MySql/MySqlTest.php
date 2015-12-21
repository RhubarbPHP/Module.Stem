<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql;

use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Tests\unit\Fixtures\Category;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\CompanyCategory;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class MySqlTest extends MySqlTestCase
{
    public function testInvalidSettingsThrowsException()
    {
        MySql::resetDefaultConnection();

        $settings = new StemSettings();
        $settings->Username = "bad-user";

        $this->setExpectedException(RepositoryConnectionException::class);

        MySql::getDefaultConnection();
    }

    public function testHasADefaultConnection()
    {
        self::SetDefaultConnectionSettings();
        MySql::resetDefaultConnection();

        $defaultConnection = MySql::getDefaultConnection();

        $this->assertInstanceOf(\PDO::class, $defaultConnection);
    }

    public function testStatementsCanBeExecuted()
    {
        // No exception should be thrown as the statement should execute.
        MySql::executeStatement("SELECT 5");

        $this->setExpectedException(RepositoryStatementException::class);

        // This should throw an exception
        MySql::executeStatement("BOSELECTA 5");
    }

    public function testCollectionRangingCreatesLimitClause()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        for ($x = 1; $x <= 20; $x++) {
            $company = new Company();
            $company->CompanyName = $x;
            $company->save();
        }

        $collection = new Collection(Company::class);
        $collection->setRange(10, 4);

        $size = sizeof($collection);

        $this->assertEquals(20, $size);

        $statement = MySql::getPreviousStatement(true);

        $this->assertContains("SQL_CALC_FOUND_ROWS", $statement);
        $this->assertContains("LIMIT 10, 4", $statement);
    }

    public function testStatementsCanBeExecutedWithParameters()
    {
        $result = MySql::executeStatement("SELECT :number", ["number" => 5]);
        $value = $result->fetchColumn(0);

        $this->assertEquals(5, $value);
    }

    public function testSingleResultCanBeFetched()
    {
        $value = MySql::returnSingleValue("SELECT :number", ["number" => 5]);

        $this->assertEquals(5, $value);
    }

    public function testResultRowCanBeFetched()
    {
        $value = MySql::returnFirstRow("SELECT :number, :number2 AS Goat", ["number" => 5, "number2" => 10]);

        $this->assertCount(2, $value);
        $this->assertEquals(10, $value["Goat"]);
    }

    public function testReload()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $company2 = new Company($company->CompanyID);

        MySql::executeStatement("UPDATE tblCompany SET CompanyName = 'test' WHERE CompanyID = :id", ["id" => $company->CompanyID]);

        $company2->reload();

        $this->assertEquals("test", $company2->CompanyName);
    }

    public function testDatabaseStorage()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        // Check to see if a record can be saved.

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $this->assertEquals(1, $company->CompanyID);

        // Check to see if the loaded record matches

        $repository = $company->getRepository();
        $repository->clearObjectCache();

        $company = new Company(1);

        $this->assertEquals("GCD", $company->CompanyName);

        // Check to see if changes are recorded
        $company->CompanyName = "GoatsBoats";
        $company->save();

        $this->assertEquals("GoatsBoats", $company->CompanyName);

        $repository->clearObjectCache();
        $company = new Company(1);
        $this->assertEquals("GoatsBoats", $company->CompanyName);

        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        $repository->clearObjectCache();

        // Check to see if a record can be saved.

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $this->assertCount(1, new Collection("Company"));

        $company->delete();

        $this->assertCount(0, new Collection("Company"));
    }

    public function testRepositoryFilters()
    {
        $group = new Group();
        $group->addFilters(new Equals("CompanyName", "GCD"));

        $list = new Collection(Company::class);
        $list->filter($group);

        sizeof($list);

        $this->assertStringStartsWith("SELECT `tblCompany`.* FROM `tblCompany` WHERE ( `tblCompany`.`CompanyName` = :", MySql::getPreviousStatement());
        $this->assertTrue($group->wasFilteredByRepository());

        $group = new Group();
        $group->addFilters(new Equals("CompanyName", "GCD"));
        $group->addFilters(new Equals("Test", "GCD"));

        $list = new Collection(Company::class);
        $list->filter($group);

        sizeof($list);

        $statement = MySql::getPreviousStatement();

        $this->assertStringStartsWith("SELECT `tblCompany`.* FROM `tblCompany` WHERE ( `tblCompany`.`CompanyName` = :", $statement);
        $this->assertFalse($group->wasFilteredByRepository());

        $group = new Group();
        $group->addFilters(new Contains("CompanyName", "GCD"));

        $list = new Collection(Company::class);
        $list->filter($group);

        sizeof($list);

        $this->assertStringStartsWith("SELECT `tblCompany`.* FROM `tblCompany` WHERE ( `tblCompany`.`CompanyName` LIKE :", MySql::getPreviousStatement());
    }

    public function testAutoHydration()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->Forename = "Andrew";
        $user->save();

        $company->Users->Append($user);

        $company->getRepository()->clearObjectCache();
        $user->getRepository()->clearObjectCache();

        $users = new Collection(User::class);
        $users->filter(new Equals("Company.CompanyName", "GCD"));

        count($users);

        $this->assertStringStartsWith("SELECT `tblUser`.*, `Company`.`CompanyID` AS `CompanyCompanyID`, `Company`.`CompanyName` AS `CompanyCompanyName`, `Company`.`Balance` AS `CompanyBalance`, `Company`.`InceptionDate` AS `CompanyInceptionDate`, `Company`.`LastUpdatedDate` AS `CompanyLastUpdatedDate`, `Company`.`KnockOffTime` AS `CompanyKnockOffTime`, `Company`.`BlueChip` AS `CompanyBlueChip`, `Company`.`ProjectCount` AS `CompanyProjectCount`, `Company`.`CompanyData` AS `CompanyCompanyData` FROM `tblUser` LEFT JOIN `tblCompany` AS `Company` ON `tblUser`.`CompanyID` = `Company`.`CompanyID` WHERE `Company`.`CompanyName` = :", MySql::getPreviousStatement());

        $company->getRepository()->clearObjectCache();
        $user->getRepository()->clearObjectCache();

        $users = new Collection(User::class);
        $users->replaceSort("Company.CompanyName", true);

        count($users);

        $this->assertStringStartsWith("SELECT `tblUser`.*, `Company`.`CompanyID` AS `CompanyCompanyID`, `Company`.`CompanyName` AS `CompanyCompanyName`, `Company`.`Balance` AS `CompanyBalance`, `Company`.`InceptionDate` AS `CompanyInceptionDate`, `Company`.`LastUpdatedDate` AS `CompanyLastUpdatedDate`, `Company`.`KnockOffTime` AS `CompanyKnockOffTime`, `Company`.`BlueChip` AS `CompanyBlueChip`, `Company`.`ProjectCount` AS `CompanyProjectCount`, `Company`.`CompanyData` AS `CompanyCompanyData` FROM `tblUser` LEFT JOIN `tblCompany` AS `Company` ON `tblUser`.`CompanyID` = `Company`.`CompanyID` ORDER BY `Company`.`CompanyName` ASC", MySql::getPreviousStatement());

        $user = $users[0];

        $this->assertCount(9, $user->ExportRawData(), "The user model should only have 9 columns. More means that the joined tables aren't being removed after the join.");

        $this->assertArrayHasKey($company->CompanyID, $company->getRepository()->cachedObjectData, "After an auto hydrated fetch the auto hydrated relationship should now be cached and ready for use in the repository");
        $this->assertCount(9, $company->getRepository()->cachedObjectData[$company->CompanyID], "The company model should only have 9 columns. More means that the joined tables aren't properly being broken up into their respective models.");
    }

    public function testManyToManyRelationships()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCategory");
        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblCompanyCategory");

        // UnitTestingSolutionSchema sets up a many to many relationship between company and category
        $company1 = new Company();
        $company2 = new Company();
        $company1->getRepository()->clearObjectCache();

        $companyCategory = new CompanyCategory();
        $companyCategory->getRepository()->clearObjectCache();

        $category1 = new Category();
        $category2 = new Category();
        $category1->getRepository()->clearObjectCache();

        $company1->CompanyName = "GCD";
        $company1->save();

        $company2->CompanyName = "UTV";
        $company2->save();

        $category1->CategoryName = "Fruit";
        $category1->save();

        $category2->CategoryName = "Apples";
        $category2->save();

        $companyCategory->CategoryID = $category1->CategoryID;
        $companyCategory->CompanyID = $company1->CompanyID;
        $companyCategory->save();

        $companyCategory = new CompanyCategory();
        $companyCategory->CategoryID = $category1->CategoryID;
        $companyCategory->CompanyID = $company2->CompanyID;
        $companyCategory->save();

        $companyCategory = new CompanyCategory();
        $companyCategory->CategoryID = $category2->CategoryID;
        $companyCategory->CompanyID = $company2->CompanyID;
        $companyCategory->save();

        // At this point GCD is in Fruit, while UTV is in Fruit and Apples.
        $company1 = new Company($company1->CompanyID);

        $this->assertCount(1, $company1->Categories);
        $this->assertCount(2, $company2->Categories);
        $this->assertCount(2, $category1->Companies);
        $this->assertCount(1, $category2->Companies);

        $this->assertEquals("UTV", $category2->Companies[0]->CompanyName);

        $this->assertStringStartsWith("SELECT `tblCompany`.*, `CategoriesRaw`.`CompanyCategoryID` AS `CompanyCategoryCompanyCategoryID`, `CategoriesRaw`.`CompanyID` AS `CompanyCategoryCompanyID`, `CategoriesRaw`.`CategoryID` AS `CompanyCategoryCategoryID` FROM `tblCompany` LEFT JOIN `tblCompanyCategory` AS `CategoriesRaw` ON `tblCompany`.`CompanyID` = `CategoriesRaw`.`CompanyID` WHERE `CategoriesRaw`.`CategoryID` = :", MySql::getPreviousStatement());
    }

    public function testManualAutoHydration()
    {
        $users = new Collection(User::class);
        $users->autoHydrate("Company");

        count($users);

        $this->assertEquals("SELECT `tblUser`.*, `Company`.`CompanyID` AS `CompanyCompanyID`, `Company`.`CompanyName` AS `CompanyCompanyName`, `Company`.`Balance` AS `CompanyBalance`, `Company`.`InceptionDate` AS `CompanyInceptionDate`, `Company`.`LastUpdatedDate` AS `CompanyLastUpdatedDate`, `Company`.`KnockOffTime` AS `CompanyKnockOffTime`, `Company`.`BlueChip` AS `CompanyBlueChip`, `Company`.`ProjectCount` AS `CompanyProjectCount`, `Company`.`CompanyData` AS `CompanyCompanyData` FROM `tblUser` LEFT JOIN `tblCompany` AS `Company` ON `tblUser`.`CompanyID` = `Company`.`CompanyID`", MySql::getPreviousStatement());
    }


    public function testOneOf()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblUser");

        $company1 = new Company();
        $company1->getRepository()->clearObjectCache();
        $company1->CompanyName = "1";
        $company1->save();

        $company2 = new Company();
        $company2->CompanyName = "2";
        $company2->save();

        $company3 = new Company();
        $company3->CompanyName = "3";
        $company3->save();

        $company4 = new Company();
        $company4->CompanyName = "4";
        $company4->save();

        $company4 = new Company();
        $company4->CompanyName = "5";
        $company4->save();

        $companies = new Collection(Company::class);
        $companies->filter(new OneOf("CompanyName", ["1", "3", "5"]));

        $this->assertCount(3, $companies);
    }

    public function testMySqlAggregateSupport()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblUser");

        $company1 = new Company();
        $company1->getRepository()->clearObjectCache();
        $company1->CompanyName = "1";
        $company1->save();

        $company2 = new Company();
        $company2->CompanyName = "2";
        $company2->save();

        $user1 = new User();
        $user1->Wage = 100;
        $company1->Users->Append($user1);

        $user2 = new User();
        $user2->Wage = 200;
        $company1->Users->Append($user2);

        $user3 = new User();
        $user3->Wage = 300;
        $company2->Users->Append($user3);

        $user4 = new User();
        $user4->Wage = 400;
        $company2->Users->Append($user4);

        $companies = new Collection(Company::class);
        $companies->addAggregateColumn(new Sum("Users.Wage"));

        $results = [];

        foreach ($companies as $company) {
            $results[] = $company->SumOfUsersWage;
        }

        $this->assertEquals([300, 700], $results);

        $sql = MySql::getPreviousStatement();

        $this->assertEquals("SELECT `tblCompany`.*, SUM( `Users`.`Wage` ) AS `SumOfUsersWage` FROM `tblCompany` LEFT JOIN `tblUser` AS `Users` ON `tblCompany`.`CompanyID` = `Users`.`CompanyID` GROUP BY `tblCompany`.`CompanyID`", $sql);

        $companies = new Collection(Company::class);
        $companies->addAggregateColumn(new Sum("Users.BigWage"));

        $results = [];

        foreach ($companies as $company) {
            $results[] = $company->SumOfUsersBigWage;
        }

        $this->assertEquals([3000, 7000], $results);
    }

    public function testIsNullFilter()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $companies = new Collection(Company::class);
        $companies->filter(new Equals("CompanyName", null));

        $this->assertEquals(0, $companies->count());

        $companies = new Collection(Company::class);
        $companies->filter(new Equals("ProjectCount", null));

        $this->assertEquals(1, $companies->count());
    }
}
