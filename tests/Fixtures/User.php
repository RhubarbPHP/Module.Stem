<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
<<<<<<< HEAD
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Decimal;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Enum;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\TinyInt;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Varchar;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
=======
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlDecimal;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnum;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlForeignKey;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\TinyMySqlInt;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlString;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b

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
class User extends \Rhubarb\Stem\Models\Model
{
	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
<<<<<<< HEAD
		$schema = new MySqlSchema( "tblUser" );

		$schema->addColumn(
			new AutoIncrement( "UserID" ),
			new ForeignKey( "CompanyID" ),
			new Enum( "UserType", "Staff", [ "Staff", "Administrator" ] ),
			new Varchar( "Username", 40 ),
			new Varchar( "Forename", 40 ),
			new Varchar( "Surname", 40 ),
			new Varchar( "Password", 120 ),
			new TinyInt( "Active", 0 ),
			new Decimal( "Wage" )
=======
		$schema = new MySqlModelSchema( "tblUser" );

		$schema->addColumn(
			new AutoIncrement( "UserID" ),
			new MySqlForeignKey( "CompanyID" ),
			new MySqlEnum( "UserType", "Staff", [ "Staff", "Administrator" ] ),
			new MySqlString( "Username", 40 ),
			new MySqlString( "Forename", 40 ),
			new MySqlString( "Surname", 40 ),
			new MySqlString( "Password", 120 ),
			new TinyMySqlInt( "Active", 0 ),
			new MySqlDecimal( "Wage" )
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
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
