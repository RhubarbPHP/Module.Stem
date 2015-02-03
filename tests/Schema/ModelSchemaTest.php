<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class ModelSchemaTest extends ModelUnitTestCase
{
	public function testMultipleColumnsCanBeAdded()
	{
		$schema = new \Rhubarb\Stem\Schema\ModelSchema( "test" );
		$schema->addColumn(
			new \Rhubarb\Stem\Schema\Columns\String( "Bob", 100 ),
			new \Rhubarb\Stem\Schema\Columns\String( "Alice", 100 )
		);

		$columns = $schema->getColumns();
		$keys = array_keys( $columns );

		$this->assertCount( 2, $columns );
		$this->assertEquals( "Alice", $keys[1] );
		$this->assertEquals( "Alice", $columns[ "Alice" ]->columnName );
	}
}
