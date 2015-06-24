<?php

namespace Rhubarb\Stem\Tests\Schema;

use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

class ModelSchemaTest extends ModelUnitTestCase
{
    public function testMultipleColumnsCanBeAdded()
    {
        $schema = new ModelSchema("test");
        $schema->addColumn(
            new String("Bob", 100),
            new String("Alice", 100)
        );

        $columns = $schema->getColumns();
        $keys = array_keys($columns);

        $this->assertCount(2, $columns);
        $this->assertEquals("Alice", $keys[1]);
        $this->assertEquals("Alice", $columns["Alice"]->columnName);
    }
}