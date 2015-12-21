<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
use Rhubarb\Stem\Schema\Columns\ForeignKey;

class ForeignKeyTest extends RhubarbTestCase
{
    public function testColumnSetsIndex()
    {
        $schema = new MySqlModelSchema("tblTest");

        $schema->addColumn(
            new ForeignKey("CompanyID")
        );

        $this->assertCount(1, $schema->indexes);
        $this->assertArrayHasKey("CompanyID", $schema->indexes);
    }
}