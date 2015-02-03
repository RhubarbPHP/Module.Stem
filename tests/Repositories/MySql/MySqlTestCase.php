<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class MySqlTestCase extends ModelUnitTestCase
{
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		\Rhubarb\Stem\Repositories\Repository::setDefaultRepositoryClassName( "\Rhubarb\Stem\Repositories\MySql\MySql" );

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
		$settings = new \Rhubarb\Stem\StemSettings();

		$settings->Host = "127.0.0.1";
		$settings->Port = 3306;
		$settings->Username = "unit-testing";
		$settings->Password = "unit-testing";
		$settings->Database = "unit-testing";
	}
}
