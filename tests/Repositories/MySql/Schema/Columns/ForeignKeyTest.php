<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Schema\Columns;

<<<<<<< HEAD
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
=======
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
use Rhubarb\Crown\Tests\RhubarbTestCase;

class ForeignKeyTest extends RhubarbTestCase
{
	public function testColumnSetsIndex()
	{
<<<<<<< HEAD
		$schema = new MySqlSchema( "tblTest" );
=======
		$schema = new MySqlModelSchema( "tblTest" );
>>>>>>> 47cd0ed3cd3eb59d8516a8eee85230348e38364b
		$schema->addColumn(
			new ForeignKey( "CompanyID" )
		);

		$this->assertCount( 1, $schema->indexes );
		$this->assertArrayHasKey( "CompanyID", $schema->indexes );
	}
}
