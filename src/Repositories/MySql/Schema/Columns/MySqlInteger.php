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
use Rhubarb\Stem\Schema\Columns\Integer;

require_once __DIR__ . "/../../../../Schema/Columns/Integer.php";
require_once __DIR__ . "/MySqlColumn.php";

class MySqlInteger extends Integer
{
    use MySqlColumn;

    protected $signed = true;

    public function getDefinition()
    {
        return "`" . $this->columnName . "` int(11) " . (!$this->signed ? "unsigned " : "") . $this->getDefaultDefinition();
    }

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new MySqlInteger($genericColumn->columnName);
    }
}