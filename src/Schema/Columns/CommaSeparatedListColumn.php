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

/**
 * Provides functionality to encode an array of items (normally ids) as a comma separated list
 */
class CommaSeparatedListColumn extends StringColumn
{
    protected $encloseWithCommas;

    /**
     * CommaSeparatedList constructor.
     * @param string $columnName
     * @param int $maximumLength
     * @param array $defaultValue
     * @param bool $encloseWithCommas If true, extra commas will be put at the start and end of the value in the repository.
     *                                This allows for easy wildcard searches for a single value in the field using SQL.
     */
    public function __construct($columnName, $maximumLength = 200, $defaultValue = [], $encloseWithCommas = false)
    {
        parent::__construct($columnName, $maximumLength, $defaultValue);

        $this->encloseWithCommas = $encloseWithCommas;
    }

    public function getPhpType()
    {
        return 'string[]';
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            if (!is_array($data[$this->columnName])) {
                return '';
            }

            $string = implode(',', $data[$this->columnName]);

            if ($this->encloseWithCommas && strlen($string) > 0) {
                return ',' . $string . ',';
            }
            return $string;
        };
    }

    public function getTransformFromRepository()
    {
        return function ($data) {
            if (empty($data[$this->columnName])) {
                return [];
            }

            $values = $data[$this->columnName];

            if ($this->encloseWithCommas) {
                $values = trim($values, ',');
            }
            return explode(',', $values);
        };
    }

    public function createStorageColumns()
    {
        return [new StringColumn($this->columnName, $this->maximumLength, '')];
    }
}
