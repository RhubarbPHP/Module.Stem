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

require_once __DIR__ . "/../../../../Schema/Columns/Date.php";

class Date extends \Rhubarb\Stem\Schema\Columns\Date
{
    use MySqlColumn;

    public function __construct($columnName)
    {
        parent::__construct($columnName, "0000-00-00");
    }

    public function getDefinition()
    {
        $sql = "`" . $this->columnName . "` date " . $this->getDefaultDefinition();
        return $sql;
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            $data = new RhubarbDateTime($data);

            if ($data->isValidDateTime()) {
                $date = $data->format("Y-m-d");
            } else {
                $date = "0000-00-00";
            }

            return $date;
        };
    }

    public function getTransformFromRepository()
    {
        return function ($data) {
            $date = new RhubarbDate($data);

            return $date;
        };
    }
}
