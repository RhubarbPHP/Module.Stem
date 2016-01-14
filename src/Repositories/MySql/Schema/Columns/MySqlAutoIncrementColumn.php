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

use Rhubarb\Stem\Repositories\MySql\Schema\MySqlIndex;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Index;

require_once __DIR__ . "/../../../../Schema/Columns/AutoIncrementColumn.php";
require_once __DIR__ . "/MySqlColumn.php";

class MySqlAutoIncrementColumn extends AutoIncrementColumn
{
    public function __construct($columnName)
    {
        parent::__construct($columnName, null);
    }

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new MySqlAutoIncrementColumn($genericColumn->columnName);
    }

    public function getDefinition()
    {
        return "`" . $this->columnName . "` int(11) unsigned NOT NULL AUTO_INCREMENT";
    }

    public function getIndex()
    {
        return new Index($this->columnName, MySqlIndex::PRIMARY);
    }
}
