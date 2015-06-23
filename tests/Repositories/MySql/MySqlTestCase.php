<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema;

class MySqlTestCase extends ModelUnitTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Repository::setDefaultRepositoryClassName(MySql::class);

        self::SetDefaultConnectionSettings();

        Log::DisableLogging();

        $unitTestingSolutionSchema = new UnitTestingSolutionSchema();
        $unitTestingSolutionSchema->checkModelSchemas();

        // Make sure the test model objects have the any other repository disconnected.
        Model::deleteRepositories();
    }

    protected static function SetDefaultConnectionSettings()
    {
        // Setup the data settings to make sure we get a connection to the unit testing database.
        $settings = new StemSettings();

        $settings->Host = "127.0.0.1";
        $settings->Port = 3306;
        $settings->Username = "unit-testing";
        $settings->Password = "unit-testing";
        $settings->Database = "unit-testing";
    }
}
