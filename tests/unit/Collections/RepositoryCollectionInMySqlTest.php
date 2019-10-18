<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Crown\DateTime\RhubarbDate;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Logging\PhpLog;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Aggregates\CountDistinct;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\TestDeclaration;

class RepositoryCollectionInMySqlTest extends RepositoryCollectionTest
{
    protected function setUp()
    {
        Log::clearLogs();

        parent::setUp();

        Collection::clearUniqueReferencesUsed();

        Log::clearLogs();

        Repository::setDefaultRepositoryClassName(MySql::class);

        $settings = StemSettings::singleton();
        $settings->host = "127.0.0.1";
        $settings->port = 3306;
        $settings->username = "unit-testing";
        $settings->password = "unit-testing";
        $settings->database = "unit-testing";

        $schemas = SolutionSchema::getAllSchemas();

        foreach($schemas as $schema){
            $schema->checkModelSchemas(0);
        }

        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblContact");
        MySql::executeStatement("TRUNCATE TABLE tblDonation");
        MySql::executeStatement("TRUNCATE TABLE tblDeclaration");

        $this->setupData();
    }

    public function testSimpleIntersections()
    {
        $collection = Company::all()->intersectWith(
            TestContact::all(),
            "CompanyID",
            "CompanyID"
        );

        count($collection);

        $sql = MySql::getPreviousStatement();

        $this->assertContains("INNER JOIN tblContact AS", $sql, "For simple inner selections (e.g. SELECT * FROM [table]) it should revert to a simple join.");
    }

    public function testCollectionsNotFilterableInRepository()
    {
        $collection = Company::find(
            new Equals("CompanyIDSquared", 4)
        );

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->UniqueIdentifier);
    }

    public function testCollectionIntersectWithNotFilterableCollections()
    {
        $example = TestContact::all();
        $example->intersectWith(
            Company::find(
                new Equals("CompanyIDSquared", 4)
            ), "CompanyID", "CompanyID");

        $this->assertCount(1, $example);
        $this->assertEquals("Mary", $example[0]->Forename);
    }

    public function testComplicatedExampleMySql()
    {
        $this->testComplicatedExample();

        $sql = MySql::getPreviousStatement();

        $this->assertContains(".`DonationID` = `", $sql, "If placeholders are working we should see this comparison in the last query");
    }

    public function testAggregatesUseHavingClause()
    {
        $this->testAggregates();

        $sql = MySql::getPreviousStatement();

        $this->assertContains("HAVING", $sql);
    }

    public function testSortsOnAggregatesUsesOrderBy()
    {
        $this->testSortsOnAggregates();

        $sql = MySql::getPreviousStatement();

        $this->assertContains("ORDER BY `SumOfBalance` DESC", $sql);
    }

    public function testPullUpsHaveCorrectTypes()
    {
        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addGroup("CompanyID"),
            "CompanyID",
            "CompanyID",
            ["DateOfBirth"]);

        $this->assertInstanceOf(RhubarbDate::class, $collection[1]->DateOfBirth);

        $collection->filter(new GreaterThan("DateOfBirth", new RhubarbDate("2016-01-01")));

        count($collection);

        $params = MySql::getPreviousParameters();

        $this->assertEquals("2016-01-01", $params["DateOfBirth"]);

        // If column types aren't registered properly stem thinks it can't aggregate on a value
        // which kicks it into manual aggregation and grouping hitting performance hard. Here
        // we check it is able to understand that pull ups can style be aggregated.

        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->intersectWith(
                    TestDeclaration::all(),
                    "ContactID",
                    "ContactID",
                    [
                        "DeclarationID"
                    ]),
            "CompanyID",
            "CompanyID",
            ["DeclarationID"])
        ->addAggregateColumn(new CountDistinct("DeclarationID"));

        $this->assertCount(2, $collection);
        $this->assertEquals(2, $collection[0]["DistinctCountOfDeclarationID"]);
        $this->assertEquals(1, $collection[1]["DistinctCountOfDeclarationID"]);

        $statement = MySql::getPreviousStatement();

        $this->assertContains("COUNT( DISTINCT `TestContact2`.`DeclarationID`", $statement);
    }

    protected function setupData()
    {
        parent::setupData();

        Log::attachLog(new PhpLog(Log::PERFORMANCE_LEVEL | Log::WARNING_LEVEL));
    }
}