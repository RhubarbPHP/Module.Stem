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

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;

require_once __DIR__ . "/ColumnFilter.php";

/**
 * Filters items which end with a given value.
 *
 * Case Sensitivity can be switched on or off in constructor
 */
class EndsWith extends ColumnFilter
{
    /**
     * The value the Column must end with
     *
     * @var string
     */
    protected $endsWith;

    /**
     * Is filter Case Sensitive?
     *
     * @var bool
     */
    protected $caseSensitive;

    public function __construct($columnName, $endsWith, $caseSensitive = false)
    {
        parent::__construct($columnName);

        $this->endsWith = $endsWith;

        $this->caseSensitive = $caseSensitive;
    }

    public function evaluate(Model $model)
    {
        $placeHolder = $this->detectPlaceHolder($this->endsWith);

        if (!$placeHolder) {
            $endsWith = $this->getTransformedComparisonValue($this->endsWith, $model);
        } else {
            $endsWith = $model[$placeHolder];
            $endsWith = $this->getTransformedComparisonValue($endsWith, $model);
        }

        if (!$this->caseSensitive) {
            $columnValue = strtolower($model[$this->columnName]);
            $endsWith = strtolower($endsWith);
        } else {
            $columnValue = $model[$this->columnName];
        }

        if (substr($columnValue, strlen($columnValue) - strlen($endsWith)) != $endsWith) {
            return true;
        }

        return false;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["endsWith"] = $this->endsWith;
        $settings["caseSensitive"] = $this->caseSensitive;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["endsWith"], $settings["caseSensitive"]);
    }
}
