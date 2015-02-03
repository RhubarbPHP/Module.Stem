<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Date;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\DateTime;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Int;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Varchar;
use Rhubarb\Stem\Schema\Columns\Boolean;
use Rhubarb\Stem\Schema\Columns\Time;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * Just a test data object to use and abuse in unit tests.
 *
 * @property int $ContactID
 * @property int $CompanyID
 * @property string $Forename
 * @property string $Surname
 * @property \Date $DateOfBirth
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class Example extends Model
{
	public $loaded = false;

	protected function createSchema()
	{
		$schema = new ModelSchema( "tblContact" );
		$schema->addColumn( new Int( "ContactID", 0 ) );
		$schema->addColumn( new Int( "CompanyID", 0 ) );
		$schema->addColumn( new Date( "DateOfBirth" ) );
		$schema->addColumn( new DateTime( "CreatedDate" ) );
		$schema->addColumn( new Varchar( "Forename", 100 ) );
		$schema->addColumn( new Varchar( "Surname", 100 ) );
		$schema->addColumn( new Boolean( "KeyContact" ) );
		$schema->addColumn( new Time( "CoffeeTime" ) );

		$schema->uniqueIdentifierColumnName = "ContactID";
		$schema->labelColumnName = "Forename";

		return $schema;
	}

	protected function onLoaded()
	{
		$this->loaded = true;
	}

	public function SimulateRaiseEvent( $eventName )
	{
		call_user_func_array( [ $this, "raiseEvent"], func_get_args() );
	}

	public function SimulateRaiseEventAfterSave( $eventName )
	{
		call_user_func_array( [ $this, "raiseEventAfterSave"], func_get_args() );
	}

	protected function getPublicPropertyList()
	{
		$properties = parent::getPublicPropertyList();
		$properties[] = "Surname";

		return $properties;
	}

	public function SetName( $name )
	{
		$this->modelData[ "Name" ] = strtoupper( $name );
	}

	public function GetMyTestValue()
	{
		return "TestValue";
	}
}
