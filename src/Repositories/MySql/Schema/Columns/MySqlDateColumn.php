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

use Rhubarb\Crown\DateTime\RhubarbDate;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\DateColumn;

require_once __DIR__ . "/../../../../Schema/Columns/DateColumn.php";
require_once __DIR__ . "/MySqlColumn.php";

class MySqlDateColumn extends DateColumn
{
    use MySqlColumn;

    public function __construct($columnName)
    {
        parent::__construct($columnName, "0000-00-00");
    }

    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return new MySqlDateColumn($genericColumn->columnName);
    }

    public function getDefinition()
    {
        return "`" . $this->columnName . "` date " . $this->getDefaultDefinition();
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            $data = new RhubarbDateTime($data[$this->columnName]);

            if ($data->isValidDateTime()) {
                $date = clone $data;
                // Normalise timezones to default system timezone when stored in DB
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                $date = $date->format("Y-m-d");
            } else {
                $date = "0000-00-00";
            }

            return $date;
        };
    }

    public function getTransformFromRepository()
    {
        return function ($data) {
            return new RhubarbDate($data[$this->columnName]);
        };
    }
}
