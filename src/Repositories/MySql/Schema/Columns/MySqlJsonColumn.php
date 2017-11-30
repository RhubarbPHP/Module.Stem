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
use Rhubarb\Stem\Schema\Columns\JsonColumn;

require_once __DIR__ . "/../../../../Schema/Columns/JsonColumn.php";

class MySqlJsonColumn extends JsonColumn
{
    protected $useMySqlJsonColumn = false;

    public function __construct($columnName, $defaultValue = null, $decodeAsArrays = false, $useMySqlJsonColumn = true)
    {
        $this->useMySqlJsonColumn = $useMySqlJsonColumn;

        parent::__construct($columnName, $defaultValue, $decodeAsArrays);
    }

    public function getDefaultDefinition()
    {
        return ($this->defaultValue === null) ? "DEFAULT NULL" : "NOT NULL";
    }

    public function getDefinition()
    {
        return "`" . $this->columnName . "` json " . $this->getDefaultDefinition();
    }

    /**
     * @param Column|JsonColumn $genericColumn
     * @return MySqlJsonColumn
     */
    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new self($genericColumn->columnName, $genericColumn->defaultValue, $genericColumn->decodeAsArrays, false);
    }

    public function createStorageColumns()
    {
        if ($this->useMySqlJsonColumn) {
            return [$this];
        }

        return parent::createStorageColumns();
    }
}
