<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql;

use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\RepositoryCollection;
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

        $settings = StemSettings::singleton();
        $settings->username = "bad-user";

        $this->setExpectedException(RepositoryConnectionException::class);

        MySql::getDefaultConnection();
    }

    public function testHasADefaultConnection()
    {
        self::setDefaultConnectionSettings();
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

        $collection = new RepositoryCollection(Company::class);
        $collection->setRange(10, 4);

        // Need to trigger a normal population of the list otherwise count is optimised
        // which is not what we're testing here.
        $collection[0];

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

    public function testDataTransforms()
    {
        $user = new User();
        $user->ProfileData = ["a" => 1];
        $user->Active = true;
        $user->save();

        User::clearObjectCache();

        $user = User::findLast();

        $this->assertEquals(["a" => 1], $user->ProfileData, "If transforms were working ProfileData would be an array");

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

        $this->assertCount(1, new RepositoryCollection("Company"));

        $company->delete();

        $this->assertCount(0, new RepositoryCollection("Company"));
    }

    public function testRepositoryFilters()
    {
        $group = new Group();
        $group->addFilters(new Equals("CompanyName", "GCD"));

        $list = new RepositoryCollection(Company::class);
        $list->filter($group);

        count($list);

        $this->assertRegExp("/SELECT .+\\.\\* FROM `tblCompany` .+ WHERE .+`CompanyName` = :/", MySql::getPreviousStatement());
        $this->assertTrue($group->wasFilteredByRepository());

        $group = new Group();
        $group->addFilters(new Equals("CompanyName", "GCD"));
        $group->addFilters(new Equals("Test", "GCD"));

        $list = new RepositoryCollection(Company::class);
        $list->filter($group);

        count($list);

        $statement = MySql::getPreviousStatement();

        $this->assertRegExp("/SELECT .+\\.\\* FROM `tblCompany` .+WHERE .+\\`CompanyName` = :/", $statement);
        $this->assertFalse($group->wasFilteredByRepository());

        $group = new Group();
        $group->addFilters(new Contains("CompanyName", "GCD"));

        $list = new RepositoryCollection(Company::class);
        $list->filter($group);

        count($list);

        $this->assertRegExp("/SELECT .+\\.\\* FROM `tblCompany` .+WHERE .+\\`CompanyName` LIKE :/", MySql::getPreviousStatement());
    }

    public function testAutoHydration()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblUser");

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->Forename = "Andrew";
        $user->save();

        $company->Users->append($user);

        $company = new Company();
        $company->CompanyName = "UTV";
        $company->save();

        $user = new User();
        $user->Forename = "Bob";
        $user->save();

        $company->Users->append($user);

        $company->getRepository()->clearObjectCache();
        $user->getRepository()->clearObjectCache();

        $users = new RepositoryCollection(User::class);
        $users->filter(new Equals("Company.CompanyName", "GCD"));

        count($users);

        $this->assertEquals("SELECT `User`.*, `Company`.`CompanyID` AS `CompanyCompanyID`, `Company`.`CompanyName` AS `CompanyCompanyName`, `Company`.`Balance` AS `CompanyBalance`, `Company`.`InceptionDate` AS `CompanyInceptionDate`, `Company`.`LastUpdatedDate` AS `CompanyLastUpdatedDate`, `Company`.`KnockOffTime` AS `CompanyKnockOffTime`, `Company`.`BlueChip` AS `CompanyBlueChip`, `Company`.`ProjectCount` AS `CompanyProjectCount`, `Company`.`CompanyData` AS `CompanyCompanyData`, `Company`.`Active` AS `CompanyActive`, `Company`.`UUID` AS `CompanyUUID` FROM `tblUser` AS `User` INNER JOIN (SELECT `Company`.* FROM `tblCompany` AS `Company` WHERE `Company`.`CompanyName` = :CompanyName GROUP BY `Company`.`CompanyID`) AS `Company` ON `User`.`CompanyID` = `Company`.`CompanyID`",
            MySql::getPreviousStatement());

        $company->getRepository()->clearObjectCache();
        $user->getRepository()->clearObjectCache();

        $users = new RepositoryCollection(User::class);
        $users->replaceSort("Company.CompanyName", true);

        count($users);

        $this->assertEquals('SELECT `User2`.*, `Company2`.CompanyName AS `CompanyName2`, `Company2`.`CompanyID` AS `Company2CompanyID`, `Company2`.`CompanyName` AS `Company2CompanyName`, `Company2`.`Balance` AS `Company2Balance`, `Company2`.`InceptionDate` AS `Company2InceptionDate`, `Company2`.`LastUpdatedDate` AS `Company2LastUpdatedDate`, `Company2`.`KnockOffTime` AS `Company2KnockOffTime`, `Company2`.`BlueChip` AS `Company2BlueChip`, `Company2`.`ProjectCount` AS `Company2ProjectCount`, `Company2`.`CompanyData` AS `Company2CompanyData`, `Company2`.`Active` AS `Company2Active`, `Company2`.`UUID` AS `Company2UUID` FROM `tblUser` AS `User2` INNER JOIN (SELECT `Company2`.* FROM `tblCompany` AS `Company2` GROUP BY `Company2`.`CompanyID`) AS `Company2` ON `User2`.`CompanyID` = `Company2`.`CompanyID` GROUP BY `User2`.`UserID` ORDER BY `Company2`.`CompanyName`',
            MySql::getPreviousStatement());

        $user = $users[0];

        $this->assertCount(11, $user->exportRawData(), "The user model should only have 10 columns. More means that the joined tables aren't being removed after the join.");

        $user = $users[1];

        $data = $company->getRepository()->cachedObjectData;
        $this->assertArrayHasKey($company->CompanyID, $data,
            "After an auto hydrated fetch the auto hydrated relationship should now be cached and ready for use in the repository");
        $this->assertCount(11, $company->getRepository()->cachedObjectData[$company->CompanyID],
            "The company model should only have 11 columns. More means that the joined tables aren't properly being broken up into their respective models.");
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

        $this->assertEquals('SELECT `Company3`.*, `CompanyCategory5`.`CompanyCategoryID` AS `CompanyCategory5CompanyCategoryID`, `CompanyCategory5`.`CompanyID` AS `CompanyCategory5CompanyID`, `CompanyCategory5`.`CategoryID` AS `CompanyCategory5CategoryID` FROM `tblCompany` AS `Company3` INNER JOIN (SELECT `CompanyCategory5`.* FROM `tblCompanyCategory` AS `CompanyCategory5` WHERE `CompanyCategory5`.`CategoryID` = :CategoryID3 GROUP BY `CompanyCategory5`.`CompanyID`) AS `CompanyCategory5` ON `Company3`.`CompanyID` = `CompanyCategory5`.`CompanyID` WHERE `Company3`.`Active` = :Active3',
            MySql::getPreviousStatement());
    }

    public function testManualAutoHydration()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->Forename = "Andrew";
        $user->save();

        $company->Users->append($user);

        $users = new RepositoryCollection(User::class);
        $users->autoHydrate("Company");

        count($users);

        $this->assertEquals('SELECT `User`.*, `Company`.`CompanyID` AS `CompanyCompanyID`, `Company`.`CompanyName` AS `CompanyCompanyName`, `Company`.`Balance` AS `CompanyBalance`, `Company`.`InceptionDate` AS `CompanyInceptionDate`, `Company`.`LastUpdatedDate` AS `CompanyLastUpdatedDate`, `Company`.`KnockOffTime` AS `CompanyKnockOffTime`, `Company`.`BlueChip` AS `CompanyBlueChip`, `Company`.`ProjectCount` AS `CompanyProjectCount`, `Company`.`CompanyData` AS `CompanyCompanyData`, `Company`.`Active` AS `CompanyActive`, `Company`.`UUID` AS `CompanyUUID` FROM `tblUser` AS `User` INNER JOIN (SELECT `Company`.* FROM `tblCompany` AS `Company` GROUP BY `Company`.`CompanyID`) AS `Company` ON `User`.`CompanyID` = `Company`.`CompanyID` GROUP BY `User`.`UserID`',
            MySql::getPreviousStatement());

        $user = $users[0];

        $data = $company->getRepository()->cachedObjectData;

        $this->assertArrayHasKey($company->CompanyID, $data,
            "After a manual hydration the relationship data should now be cached and ready for use in the repository");
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

        $companies = new RepositoryCollection(Company::class);
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
        $company1->Users->append($user1);

        $user2 = new User();
        $user2->Wage = 200;
        $company1->Users->append($user2);

        $user3 = new User();
        $user3->Wage = 300;
        $company2->Users->append($user3);

        $user4 = new User();
        $user4->Wage = 400;
        $company2->Users->append($user4);

        $companies = new RepositoryCollection(Company::class);
        $companies->addAggregateColumn(new Sum("Users.Wage"));

        $results = [];

        foreach ($companies as $company) {
            $results[] = $company->SumOfUsersWage;
        }

        $sql = MySql::getPreviousStatement();

        $this->assertEquals('SELECT `Company`.*, `UnitTestUser`.SumOfUsersWage AS `SumOfUsersWage`, `UnitTestUser`.`UserID` AS `UnitTestUserUserID`, `UnitTestUser`.`CompanyID` AS `UnitTestUserCompanyID`, `UnitTestUser`.`UserType` AS `UnitTestUserUserType`, `UnitTestUser`.`Username` AS `UnitTestUserUsername`, `UnitTestUser`.`Forename` AS `UnitTestUserForename`, `UnitTestUser`.`Surname` AS `UnitTestUserSurname`, `UnitTestUser`.`Password` AS `UnitTestUserPassword`, `UnitTestUser`.`Active` AS `UnitTestUserActive`, `UnitTestUser`.`Wage` AS `UnitTestUserWage`, `UnitTestUser`.`ProfileData` AS `UnitTestUserProfileData` FROM `tblCompany` AS `Company` INNER JOIN (SELECT `UnitTestUser`.*, SUM( `UnitTestUser`.`Wage`) AS `SumOfUsersWage` FROM `tblUser` AS `UnitTestUser` GROUP BY `UnitTestUser`.`CompanyID`) AS `UnitTestUser` ON `Company`.`CompanyID` = `UnitTestUser`.`CompanyID` GROUP BY `Company`.`CompanyID`',
            $sql);
        $this->assertEquals([300, 700], $results);

        $companies = new RepositoryCollection(Company::class);
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

        $companies = new RepositoryCollection(Company::class);
        $companies->filter(new Equals("CompanyName", null));

        $this->assertEquals(0, $companies->count());

        $companies = new RepositoryCollection(Company::class);
        $companies->filter(new Equals("ProjectCount", null));

        $this->assertEquals(1, $companies->count());
    }
}
