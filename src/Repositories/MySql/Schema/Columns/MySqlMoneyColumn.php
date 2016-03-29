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

use Rhubarb\Stem\Schema\Columns\Column;

require_once __DIR__ . '/MySqlDecimalColumn.php';

/**
 * Class Money
 *
 * Implementation of decimal column type accurate to 2dp
 */
class MySqlMoneyColumn extends MySqlDecimalColumn
{
    /**
     * @param string $columnName
     * @param int $totalDigits
     * @param int $defaultValue
     */
    public function __construct($columnName, $totalDigits = 8, $defaultValue = 0)
    {
        parent::__construct($columnName, $totalDigits, 2, $defaultValue);
    }

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new self($genericColumn->columnName, $genericColumn->totalDigits, $genericColumn->defaultValue);
    }
}
