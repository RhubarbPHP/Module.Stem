<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Logging\PhpLog;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemSettings;

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

        $this->setupData();
    }

    protected function setupData()
    {
        parent::setupData();

        Log::attachLog(new PhpLog(Log::PERFORMANCE_LEVEL));
    }
}