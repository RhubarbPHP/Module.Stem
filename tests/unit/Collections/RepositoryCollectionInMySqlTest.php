<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Logging\PhpLog;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;

class RepositoryCollectionInMySqlTest extends RepositoryCollectionTest
{
    protected function setUp()
    {
        Log::clearLogs();

        parent::setUp();

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

    protected function setupData()
    {
        parent::setupData();

        Log::attachLog(new PhpLog(Log::PERFORMANCE_LEVEL));
    }
}