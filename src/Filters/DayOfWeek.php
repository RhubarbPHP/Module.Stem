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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;

/**
 */
class DayOfWeek extends ColumnFilter
{
    protected $validDays = [];

    /**
     * @param $columnName
     * @param array $validDays The days to filter for. 0 based starting with monday e.g. 0 = Monday, 1 = Tuesday...
     */
    public function __construct($columnName, $validDays = [])
    {
        parent::__construct($columnName);

        $this->validDays = $validDays;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["validDays"] = $this->validDays;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["validDays"]);
    }

    /**
     * Chooses whether to remove the model from the list or not
     *
     * Returns true to remove it, false to keep it.
     *
     * @param Model $model
     * @return array
     */
    public function evaluate(Model $model)
    {
        $filter = false;

        if (!$model[$this->columnName] instanceof RhubarbDateTime) {
            $filter = true;
        } else {
            if (!in_array($model[$this->columnName]->format("N") - 1, $this->validDays)) {
                $filter = true;
            }
        }

        return $filter;
    }
}
