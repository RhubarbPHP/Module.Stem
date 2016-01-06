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
 * Data filter used to keep all records with a variable which is greater than (or optionally equal to) a particular variable.
 */
class GreaterThan extends ColumnFilter
{
    /**
     * The value that the column must be greater than to be included.
     *
     * @var string
     */
    public $greaterThan;

    /**
     * Whether or not to include values that are equal
     *
     * @var string
     */
    protected $inclusive;

    public function __construct($columnName, $greaterThan, $inclusive = false)
    {
        parent::__construct($columnName);

        $this->greaterThan = $greaterThan;
        $this->inclusive = $inclusive;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["greaterThan"] = $this->greaterThan;
        $settings["inclusive"] = $this->inclusive;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["greaterThan"], $settings["inclusive"]);
    }

    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        $placeHolder = $this->detectPlaceHolder($this->greaterThan);

        if (!$placeHolder) {
            $greaterThan = $this->getTransformedComparisonValue($this->greaterThan, $list);

            if (is_string($greaterThan)) {
                $greaterThan = strtolower($greaterThan);
            }
        }

        foreach ($list as $item) {
            if ($placeHolder) {
                $greaterThan = $this->getTransformedComparisonValue($item[$placeHolder], $list);

                if (is_string($greaterThan)) {
                    $greaterThan = strtolower($greaterThan);
                }
            }

            $valueToTest = $item[$this->columnName];

            if (is_string($valueToTest)) {
                $valueToTest = strtolower($valueToTest);
            }

            if (($valueToTest < $greaterThan) 
                || ($this->inclusive == false && $valueToTest == $greaterThan)
            ) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }
}