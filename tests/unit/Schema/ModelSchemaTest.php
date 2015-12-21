<?php

namespace Rhubarb\Stem\Tests\unit\Schema;

use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class ModelSchemaTest extends ModelUnitTestCase
{
    public function testMultipleColumnsCanBeAdded()
    {
        $schema = new ModelSchema("test");
        $schema->addColumn(
            new StringColumn("Bob", 100),
            new StringColumn("Alice", 100)
        );

        $columns = $schema->getColumns();
        $keys = array_keys($columns);

        $this->assertCount(2, $columns);
        $this->assertEquals("Alice", $keys[1]);
        $this->assertEquals("Alice", $columns["Alice"]->columnName);
    }
}