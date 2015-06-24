<?php

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Schema\Columns\Column;

class ColumnTest extends RhubarbTestCase
{
    public function testColumnCreationStoresNameAndDefault()
    {
        $column = new Column("Forename", "SensibleDefault");

        $this->assertEquals("Forename", $column->columnName);
        $this->assertEquals("SensibleDefault", $column->defaultValue);
    }
}
