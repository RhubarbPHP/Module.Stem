<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class ForeignKeyTest extends RhubarbTestCase
{
    public function testColumnSetsIndex()
    {
        $schema = new ModelSchema("tblTest");

        $schema->addColumn(
            new ForeignKeyColumn("CompanyID")
        );

        $schema = MySqlModelSchema::fromGenericSchema($schema);
        $this->assertCount(1, $schema->getIndexes());
        $this->assertArrayHasKey("CompanyID", $schema->getIndexes());
    }
}
