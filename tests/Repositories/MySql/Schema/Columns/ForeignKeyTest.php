<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class ForeignKeyTest extends RhubarbTestCase
{
	public function testColumnSetsIndex()
	{
		$schema = new MySqlSchema( "tblTest" );
		$schema->addColumn(
			new ForeignKey( "CompanyID" )
		);

		$this->assertCount( 1, $schema->indexes );
		$this->assertArrayHasKey( "CompanyID", $schema->indexes );
	}
}
