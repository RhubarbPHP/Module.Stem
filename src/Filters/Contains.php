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

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;

/**
 * Filter items containing a given value.
 *
 * Case Sensitivity can be switched on or off in constructor
 */
class Contains extends ColumnFilter
{
    /**
     * What the given column must contain
     *
     * @var string
     */
    protected $contains;

    /**
     * Whether or not the comparison is case sensitive
     *
     * @var bool
     */
    protected $caseSensitive;

    public function __construct($columnName, $contains, $caseSensitive = false)
    {
        parent::__construct($columnName);

        $this->contains = $contains;

        $this->caseSensitive = $caseSensitive;
    }

    /**
     *
     * @param Model $model
     * @return array
     */
    public function evaluate(Model $model)
    {
        $searchMethod = $this->caseSensitive ? 'strpos' : 'stripos';

        if (strlen($model[$this->columnName]) < strlen($this->contains)
            || $searchMethod($model[$this->columnName], $this->contains) === false
        ) {
            return true;
        }

        return false;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["contains"] = $this->contains;
        $settings["caseSensitive"] = $this->caseSensitive;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["contains"], $settings["caseSensitive"]);
    }
}
