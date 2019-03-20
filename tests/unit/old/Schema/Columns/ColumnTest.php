<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
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
