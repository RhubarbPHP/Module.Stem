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

require_once __DIR__ . "/../../../../Schema/Columns/TimeColumn.php";
require_once __DIR__ . "/MySqlColumn.php";

use Rhubarb\Crown\DateTime\RhubarbTime;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\TimeColumn;

class MySqlTimeColumn extends TimeColumn
{
    use MySqlColumn;

    public function __construct($columnName)
    {
        parent::__construct($columnName, "00:00:00");
    }

    public function getDefinition()
    {
        return "`" . $this->columnName . "` time " . $this->getDefaultDefinition();
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            $data = new RhubarbTime($data[$this->columnName]);

            if ($data->isValidDateTime()) {
                $date = $data->format("H:i:s");
            } else {
                $date = "00:00:00";
            }

            return $date;
        };
    }

    public function getTransformFromRepository()
    {
        return function ($data) {
            return new RhubarbTime($data[$this->columnName]);
        };
    }

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new self($genericColumn->columnName);
    }
}