<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Schema\Columns\Boolean;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\Columns\Decimal;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnum;
use Rhubarb\Stem\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\AndGroup;

/**
 *
 * @property int $UserID
 * @property int $CompanyID
 * @property string $Username
 * @property string $Forename
 * @property string $Surname
 * @property string $Password
 * @property bool $Active
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class User extends Model
{
	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
		$schema = new ModelSchema( "tblUser" );

		$schema->addColumn(
			new AutoIncrement( "UserID" ),
			new ForeignKey( "CompanyID" ),
			new MySqlEnum( "UserType", "Staff", [ "Staff", "Administrator" ] ),
			new String( "Username", 40 ),
			new String( "Forename", 40 ),
			new String( "Surname", 40 ),
			new String( "Password", 120 ),
			new Boolean( "Active", false ),
			new Decimal( "Wage" )
		);

		$schema->uniqueIdentifierColumnName = "UserID";
		$schema->labelColumnName = "FullName";

		return $schema;
	}

	public function GetBigWage()
	{
		return $this->Wage * 10;
	}

	public function GetFullName()
	{
		return $this->Forename." ".$this->Surname;
	}

	public static function FromUsername( $username )
	{
		return self::findFirst( new Equals( "Username", $username ) );
	}

	public static function find( Filter $filter = null )
	{
		$activeFilter = new Equals( 'Active', 1 );
		if( $filter === null )
		{
			$filter = $activeFilter;
		}
		else
		{
			$filter = new AndGroup( [ $filter, $activeFilter ] );
		}

		return parent::find( $filter );
	}
}
