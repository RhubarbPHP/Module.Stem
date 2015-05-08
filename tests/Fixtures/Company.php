<?php

namespace Rhubarb\Stem\Tests\Fixtures;

/**
 * A sample data object modelling a company for use with unit testing.
 *
 * @property int $CompanyID
 * @property string $CompanyName
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Validation\HasValue;
use Rhubarb\Stem\Models\Validation\Validator;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
<<<<<<< HEAD
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Date;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\DateTime;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Int;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\JsonText;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Money;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Time;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\TinyInt;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Varchar;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
=======
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlDate;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlDateTime;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlInteger;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Json;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlMoney;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlTime;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\TinyMySqlInt;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlString;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b

class Company extends Model
{

	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
<<<<<<< HEAD
		$schema = new MySqlSchema( "tblCompany" );
=======
		$schema = new MySqlModelSchema( "tblCompany" );
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
		$schema->uniqueIdentifierColumnName = "CompanyID";

		$companyId = new AutoIncrement( "CompanyID" );

		$schema->addColumn( $companyId );
<<<<<<< HEAD
		$schema->addColumn( new Varchar( "CompanyName", 200 ) );
		$schema->addColumn( new Money( "Balance" ) );
		$schema->addColumn( new Date( "InceptionDate" ) );
		$schema->addColumn( new DateTime( "LastUpdatedDate" ) );
		$schema->addColumn( new Time( "KnockOffTime" ) );
		$schema->addColumn( new TinyInt( "BlueChip", 0 ) );
		$schema->addColumn( new Int( "ProjectCount" ) );
		$schema->addIndex( new Index( "CompanyID", Index::PRIMARY ) );
		$schema->addColumn( new JsonText( "CompanyData" ) );
		$schema->addColumn( new TinyInt( "Active", 1 ) );
=======
		$schema->addColumn( new MySqlString( "CompanyName", 200 ) );
		$schema->addColumn( new MySqlMoney( "Balance" ) );
		$schema->addColumn( new MySqlDate( "InceptionDate" ) );
		$schema->addColumn( new MySqlDateTime( "LastUpdatedDate" ) );
		$schema->addColumn( new MySqlTime( "KnockOffTime" ) );
		$schema->addColumn( new TinyMySqlInt( "BlueChip", 0 ) );
		$schema->addColumn( new MySqlInteger( "ProjectCount" ) );
		$schema->addIndex( new Index( "CompanyID", Index::PRIMARY ) );
		$schema->addColumn( new Json( "CompanyData" ) );
		$schema->addColumn( new TinyMySqlInt( "Active", 1 ) );
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b

		$schema->labelColumnName = "CompanyName";

		return $schema;
	}

	protected function getPublicPropertyList()
	{
		$list = parent::getPublicPropertyList();
		$list[] = "Balance";

		return $list;
	}

	public function GetCompanyIDSquared()
	{
		return $this->CompanyID * $this->CompanyID;
	}

	protected function getConsistencyValidationErrors()
	{
		$errors = [];

		if ( !$this->CompanyName )
		{
			$errors[ "CompanyName" ] = "Company name must be supplied";
		}

		return $errors;
	}

	protected function CreateConsistencyValidator()
	{
		$validator = new Validator();
		$validator->validations[] = new HasValue( "CompanyName" );

		return $validator;
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