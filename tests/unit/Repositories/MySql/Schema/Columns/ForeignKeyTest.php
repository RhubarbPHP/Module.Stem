<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;

class ForeignKeyTest extends RhubarbTestCase
{
    public function testColumnSetsIndex()
    {
        $schema = new MySqlModelSchema("tblTest");

        $schema->addColumn(
            new ForeignKeyColumn("CompanyID")
        );

        $schema = MySqlModelSchema::fromGenericSchema($schema);
        $this->assertCount(1, $schema->indexes);
        $this->assertArrayHasKey("CompanyID", $schema->indexes);
    }
}