<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Stem\Repositories\MySql\Schema\Columns;

require_once __DIR__ . "/../../../../Schema/Columns/Float.php";

use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\Decimal;

/**
 * MySql DECIMAL column data type
 */
class MySqlDecimal extends Decimal
{
    use MySqlColumn;

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new MySqlDecimal(
            $genericColumn->columnName,
            $genericColumn->totalDigits,
            $genericColumn->decimalDigits,
            $genericColumn->defaultValue
        );
    }

    /**
     * @return string
     */
    public function getDefaultDefinition()
    {
        return ($this->defaultValue === null) ? "DEFAULT NULL"
            : "NOT NULL DEFAULT '" . number_format($this->defaultValue, $this->decimalDigits, '.', '') . "'";
    }

    /**
     * @return string The definition string needed to update the back end storage schema to match
     */
    public function getDefinition()
    {
        return "`" . $this->columnName . "` DECIMAL(" . $this->totalDigits . "," . $this->decimalDigits . ") " . $this->getDefaultDefinition();
    }
}