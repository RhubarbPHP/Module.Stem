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
 * Keeps all records which have a value matching one of the values in the given array
 */
class OneOf extends ColumnFilter
{
    /**
     * The array of values to search for this filter
     *
     * @var array
     */
    protected $oneOf;

    /**
     * Used to determine whether to use in_arrayi or $this->in_arrayi()
     *
     * @var bool
     */
    protected $caseSensitive;


    public function __construct($columnName, $oneOf = [], $caseSensitive = true)
    {
        parent::__construct($columnName);

        if (!is_array($oneOf)) {
            $oneOf = [$oneOf];
        }

        $this->oneOf = $oneOf;
        $this->caseSensitive = $caseSensitive;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["oneOf"] = $this->oneOf;

        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["oneOf"]);
    }

    /**
     * Chooses whether to remove the model from the list or not
     *
     * Returns true to remove it, false to keep it.
     *
     * @param Model $model
     * @return bool
     */
    public function evaluate(Model $model)
    {
        if ($this->caseSensitive) {
            if (!in_array($model[$this->columnName], $this->oneOf)) {
                return true;
            }
        } else {
            if (!$this->in_arrayi($model[$this->columnName], $this->oneOf)) {
                return true;
            }
        }

        return false;
    }

    protected function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
}