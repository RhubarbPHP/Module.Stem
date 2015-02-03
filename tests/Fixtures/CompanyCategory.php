<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Int;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\ModelSchema;

/** 
 * 
 *
 * @package Rhubarb\Stem\Tests\Fixtures
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class CompanyCategory extends Model
{

	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
		$schema = new MySqlSchema( "tblCompanyCategory" );
		$schema->addColumn(
			new AutoIncrement( "CompanyCategoryID" ),
			new Int( "CompanyID" ),
			new Int( "CategoryID" )
		);

		$schema->uniqueIdentifierColumnName = "CompanyCategoryID";

		return $schema;
	}
}