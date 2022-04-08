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

namespace Rhubarb\Stem\Schema\Columns;

require_once __DIR__ . "/Column.php";

class IntegerColumn extends Column
{
    /**
     * @deprecated to be removed in v2
     * @var bool
     */
    public static $cast = true;
    protected $maintainNull;

    public function __construct($columnName, $defaultValue = null, $maintainNull = false)
    {
        parent::__construct($columnName, $defaultValue);

        $this->maintainNull = $maintainNull;
    }

    public function getPhpType()
    {
        return "int";
    }

    public function getTransformIntoModelData()
    {
        return IntegerColumn::$cast
            ? function ($value) {
                if($this->maintainNull && $value === null) {
                    return $value;
                } else {
                    return (int)$value;
                }
            }
            : parent::getTransformIntoModelData();
    }

    public function getTransformFromRepository()
    {
        return IntegerColumn::$cast
            ? function ($data) {
                if($this->maintainNull && $data[$this->columnName] === null) {
                    return $data[$this->columnName];
                } else {
                    return (int)$data[$this->columnName];
                }
            }
            : parent::getTransformFromRepository();
    }
}
