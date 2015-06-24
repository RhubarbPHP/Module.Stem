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

namespace Rhubarb\Stem\Filters;

require_once __DIR__ . "/ColumnFilter.php";

use Rhubarb\Stem\Collections\Collection;

/**
 * Keeps all records with a value that starts with a given value.
 */
class StartsWith extends ColumnFilter
{
    /**
     * The string that columnName must start With to be included
     * @var string
     */
    protected $startsWith;

    /**
     * Whether or not the comparison is caseSensitive
     * @var boolean
     */
    protected $caseSensitive;

    /**
     * @param string $columnName
     * @param string $startsWith
     * @param bool $caseSensitive
     */
    public function __construct($columnName, $startsWith, $caseSensitive = false)
    {
        parent::__construct($columnName);

        $this->startsWith = $startsWith;
        $this->caseSensitive = $caseSensitive;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["startsWith"] = $this->startsWith;
        $settings["caseSensitive"] = $this->caseSensitive;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["startsWith"], $settings["caseSensitive"]);
    }

    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        foreach ($list as $item) {
            if (!$this->caseSensitive) {
                $columnValue = strtolower($item[$this->columnName]);
                $startsWith = strtolower($this->startsWith);
            } else {
                $columnValue = $item[$this->columnName];
                $startsWith = $this->startsWith;
            }

            if (substr($columnValue, 0, strlen($startsWith)) != $startsWith) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }
}