<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema;

use Rhubarb\Stem\Exceptions\SchemaException;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlComparisonSchema;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlIndex;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Index;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class MySqlSchemaTest extends MySqlTestCase
{
    public function testEnumRequiresDefault()
    {
        $enum = new MySqlEnumColumn("Test", "A", ["A"]);

        $this->assertEquals("A", $enum->defaultValue);

        $this->setExpectedException(SchemaException::class);

        new MySqlEnumColumn("Test", null, ["A", "B"]);
    }

    public function testSchemaIsCreated()
    {
        MySql::executeStatement("DROP TABLE IF EXISTS tblExample");

        $schema = new ModelSchema("tblExample");

        $schema->addColumn(new AutoIncrementColumn("ID"));
        $schema->addColumn(new StringColumn("Name", 40, "StrangeDefault"));
        $schema->addColumn(new MySqlEnumColumn("Type", "A", ["A", "B", "C"]));

        $schema->addIndex(new Index("ID", MySqlIndex::PRIMARY));

        $schema = MySqlModelSchema::fromGenericSchema($schema);

        $schema->checkSchema(Repository::getNewDefaultRepository(new TestContact()));

        $newSchema = MySqlComparisonSchema::fromTable("tblExample");
        $columns = $newSchema->columns;

        $this->assertCount(3, $columns);
        $this->assertEquals("`Name` varchar(40) NOT NULL DEFAULT 'StrangeDefault'", $columns["Name"]);
        $this->assertContains("`Type` enum('A','B','C') NOT NULL DEFAULT 'A'", $columns["Type"]);

        // Check schema equivalence
        $this->assertTrue($newSchema == MySqlComparisonSchema::fromMySqlSchema($schema));
    }

    public function testSchemaIsModified()
    {
        // Note this test relies on the previous test to leave tblExample behind.

        $schema = new ModelSchema("tblExample");

        $schema->addColumn(new AutoIncrementColumn("ID"));
        $schema->addColumn(new StringColumn("Name", 40, "StrangeDefault"));
        $schema->addColumn(new MySqlEnumColumn("Type", "A", ["A", "B", "C"]));
        $schema->addColumn(new MySqlEnumColumn("Type", "B", ["A", "B", "C", "D"]));
        $schema->addColumn(new StringColumn("Town", 60, null));

        $schema->addIndex(new Index("ID", MySqlIndex::PRIMARY));

        $schema = MySqlModelSchema::fromGenericSchema($schema);

        $schema->checkSchema(Repository::getNewDefaultRepository(new TestContact()));

        $newSchema = MySqlComparisonSchema::fromTable("tblExample");

        $columns = $newSchema->columns;

        $this->assertCount(4, $columns);
        $this->assertEquals("`Town` varchar(60) DEFAULT NULL", $columns["Town"]);
        $this->assertEquals("`Type` enum('A','B','C','D') NOT NULL DEFAULT 'B'", $columns["Type"]);
    }

    public function testSchemaSetsIndexAndIdentifierWhenAutoIncrementAdded()
    {
        $schema = new ModelSchema("tblTest");

        $schema->addColumn(new AutoIncrementColumn("TestID"));

        $schema = MySqlModelSchema::fromGenericSchema($schema);

        $this->assertEquals("TestID", $schema->uniqueIdentifierColumnName);
        $this->assertEquals(MySqlIndex::PRIMARY, $schema->getIndex("Primary")->indexType);
    }
}
