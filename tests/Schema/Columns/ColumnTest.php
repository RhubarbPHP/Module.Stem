<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Schema\Columns\Column;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class ColumnTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
    public function testColumnCreationStoresNameAndDefault()
    {
        $column = new Column("Forename", "SensibleDefault");

        $this->assertEquals("Forename", $column->columnName);
        $this->assertEquals("SensibleDefault", $column->defaultValue);
    }
}
