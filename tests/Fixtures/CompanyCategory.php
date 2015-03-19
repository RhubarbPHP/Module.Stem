<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
<<<<<<< HEAD
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Int;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
=======
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlInteger;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
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
<<<<<<< HEAD
		$schema = new MySqlSchema( "tblCompanyCategory" );
		$schema->addColumn(
			new AutoIncrement( "CompanyCategoryID" ),
			new Int( "CompanyID" ),
			new Int( "CategoryID" )
=======
		$schema = new MySqlModelSchema( "tblCompanyCategory" );
		$schema->addColumn(
			new AutoIncrement( "CompanyCategoryID" ),
			new MySqlInteger( "CompanyID" ),
			new MySqlInteger( "CategoryID" )
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
		);

		$schema->uniqueIdentifierColumnName = "CompanyCategoryID";

		return $schema;
	}
}