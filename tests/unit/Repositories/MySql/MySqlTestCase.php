<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\UnitTestingSolutionSchema;

class MySqlTestCase extends ModelUnitTestCase
{

    protected function setUp()
    {
        parent::setUp();

        // Make sure the test model objects have the any other repository disconnected.
        Model::deleteRepositories();

        Repository::setDefaultRepositoryClassName(MySql::class);

        self::setDefaultConnectionSettings();

        Log::disableLogging();

        $unitTestingSolutionSchema = new UnitTestingSolutionSchema();
        $unitTestingSolutionSchema->checkModelSchemas();
    }

    protected static function setDefaultConnectionSettings()
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
