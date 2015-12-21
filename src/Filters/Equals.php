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
use Rhubarb\Stem\Models\Model;

/**
 * Data filter used to keep all records with a variable which is equal to a particular variable.
 */
class Equals extends ColumnFilter
{
    /**
     * The Value that must be equal to the column
     *
     * @var string
     */
    protected $equalTo;

    public function __construct($columnName, $equalTo)
    {
        parent::__construct($columnName);

        $this->equalTo = $equalTo;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["equalTo"] = $this->equalTo;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["equalsTo"]);
    }

    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        $placeHolder = $this->detectPlaceHolder($this->equalTo);

        if (!$placeHolder) {
            $equalTo = $this->getTransformedComparisonValue($this->equalTo, $list);
        }

        foreach ($list as $item) {
            if ($placeHolder) {
                $equalTo = $item[$placeHolder];
                $equalTo = $this->getTransformedComparisonValue($equalTo, $list);
            }

            if ($item[$this->columnName] != $equalTo) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }

    public function setFilterValuesOnModel(Model $model)
    {
        $model[$this->columnName] = $this->equalTo;
    }

    /**
     * @return string
     */
    public function getEqualTo()
    {
        return $this->equalTo;
    }
}
