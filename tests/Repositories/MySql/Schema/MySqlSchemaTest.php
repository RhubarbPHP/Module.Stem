<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Schema;

use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnum;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlString;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlComparisonSchema;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class MySqlSchemaTest extends MySqlTestCase
{
	public function testEnumRequiresDefault()
	{
		$enum = new MySqlEnum( "Test", "A", array( "A" ) );

		$this->assertEquals( "A", $enum->defaultValue );

		$this->setExpectedException( "\Rhubarb\Stem\Exceptions\SchemaException" );

		$enum = new MySqlEnum( "Test", "B", array( "A" ) );
	}

	public function testSchemaIsCreated()
	{
		MySql::executeStatement( "DROP TABLE IF EXISTS tblExample" );

		$schema = new MySqlModelSchema( "tblExample" );

		$schema->addColumn( new AutoIncrement( "ID" ) );
		$schema->addColumn( new MySqlString( "Name", 40, "StrangeDefault" ) );
		$schema->addColumn( new MySqlEnum( "Type", "A", array( "A", "B", "C" ) ) );

		$schema->addIndex( new Index( "ID", Index::PRIMARY ) );

		$schema->checkSchema();

		$newSchema = MySqlComparisonSchema::fromTable( "tblExample" );
		$columns = $newSchema->columns;

		$this->assertCount( 3, $columns );
		$this->assertEquals( "`Name` varchar(40) NOT NULL DEFAULT 'StrangeDefault'", $columns[ "Name" ] );
		$this->assertContains( "`Type` enum('A','B','C') NOT NULL DEFAULT 'A'", $columns[ "Type" ] );

		// Check schema equivalence
		$this->assertTrue( $newSchema == MySqlComparisonSchema::fromMySqlSchema( $schema ) );
	}

	public function testSchemaIsModified()
	{
		// Note this test relies on the previous test to leave tblExample behind.

		$schema = new MySqlModelSchema( "tblExample" );

		$schema->addColumn( new AutoIncrement( "ID" ) );
		$schema->addColumn( new MySqlString( "Name", 40, "StrangeDefault" ) );
		$schema->addColumn( new MySqlEnum( "Type", "A", array( "A", "B", "C" ) ) );

		$schema->addIndex( new Index( "ID", Index::PRIMARY ) );
		$schema->addColumn( new MySqlEnum( "Type", "B", array( "A", "B", "C", "D" ) ) );
		$schema->addColumn( new MySqlString( "Town", 60, null ) );
		$schema->checkSchema();

		$newSchema = MySqlComparisonSchema::fromTable( "tblExample" );

		$columns = $newSchema->columns;

		$this->assertCount( 4, $columns );
		$this->assertEquals( "`Town` varchar(60) DEFAULT NULL", $columns[ "Town" ] );
		$this->assertEquals( "`Type` enum('A','B','C','D') NOT NULL DEFAULT 'B'", $columns[ "Type" ] );
	}

	public function testSchemaSetsIndexAndIdentifierWhenAutoIncrementAdded()
	{
		$schema = new MySqlModelSchema( "tblTest" );
		$schema->addColumn( new AutoIncrement( "TestID" ) );

		$this->assertEquals( "TestID", $schema->uniqueIdentifierColumnName );
		$this->assertEquals( Index::PRIMARY, $schema->indexes[ "Primary" ]->indexType );
	}
}
