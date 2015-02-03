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

class Company extends Model
{

	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
		$schema = new MySqlSchema( "tblCompany" );
		$schema->uniqueIdentifierColumnName = "CompanyID";

		$companyId = new AutoIncrement( "CompanyID" );

		$schema->addColumn( $companyId );
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