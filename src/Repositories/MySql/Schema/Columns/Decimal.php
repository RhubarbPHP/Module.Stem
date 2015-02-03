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

use Rhubarb\Stem\Schema\Columns\Float;

/**
 * MySql DECIMAL column data type
 */
class Decimal extends Float
{
    use MySqlColumn;

    private $precision;

    /**
     * @param string $columnName
     * @param string $precision MySql decimal precision format
     * @param int $defaultValue
     */
    public function __construct($columnName, $precision = '8,2', $defaultValue = 0)
    {
        parent::__construct($columnName, $defaultValue);

        $this->precision = $precision;
    }

    /**
     * @return string
     */
    public function getDefaultDefinition()
    {
        $precisionParts = explode(',', $this->precision);

        $decimalPrecision = count($precisionParts) === 2 ? $precisionParts[1] : 0;

        return ($this->defaultValue === null) ? "DEFAULT NULL"
            : "NOT NULL DEFAULT '" . number_format($this->defaultValue, $decimalPrecision, '.', '') . "'";
    }

    /**
     * @return string The definition string needed to update the back end storage schema to match
     */
    public function getDefinition()
    {
        $sql = "`" . $this->columnName . "` DECIMAL(" . $this->precision . ") " . $this->getDefaultDefinition();

        return $sql;
    }
}