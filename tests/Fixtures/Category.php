<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
<<<<<<< HEAD
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Varchar;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
=======
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlString;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 *
 *
 * @package Rhubarb\Stem\Tests\Fixtures
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class Category extends Model
{

	/**
	 * Returns the schema for this data object.
	 *
	 * @return \Rhubarb\Stem\Schema\ModelSchema
	 */
	protected function createSchema()
	{
<<<<<<< HEAD
		$schema = new MySqlSchema( "tblCategory" );

		$schema->addColumn(
			new AutoIncrement( "CategoryID" ),
			new Varchar( "CategoryName", 50 )
=======
		$schema = new MySqlModelSchema( "tblCategory" );

		$schema->addColumn(
			new AutoIncrement( "CategoryID" ),
			new MySqlString( "CategoryName", 50 )
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
		);

		$schema->uniqueIdentifierColumnName = "CategoryID";

		return $schema;
	}
}