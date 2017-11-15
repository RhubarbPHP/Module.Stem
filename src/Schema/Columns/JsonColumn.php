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

require_once __DIR__ . '/LongStringColumn.php';

class JsonColumn extends LongStringColumn
{
    protected $decodeAsArrays;

    /**
     * @param $columnName
     * @param null $defaultValue
     * @param bool $decodeAsArrays True if the decoded JSON values should be returned as arrays instead of objects.
     */
    public function __construct($columnName, $defaultValue = null, $decodeAsArrays = false)
    {
        parent::__construct($columnName, $defaultValue);

        $this->decodeAsArrays = $decodeAsArrays;
    }

    public function getPhpType()
    {
        return '\stdClass';
    }

    public function getTransformFromRepository()
    {
        return function ($data) {
            return json_decode($data[$this->columnName], $this->decodeAsArrays);
        };
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            return json_encode($data[$this->columnName]);
        };
    }

    public function createStorageColumns()
    {
        return [new LongStringColumn($this->columnName, $this->defaultValue)];
    }
}
