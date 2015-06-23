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
 * Filters items less than (or optionally equal to) a particular value
 */
class LessThan extends ColumnFilter
{
    /**
     * The value that the column must be less than
     * @var string
     */
    public $lessThan;

    /**
     * Whether or not to include values that are equal
     *
     * @var string
     */
    protected $inclusive;

    public function __construct($columnName, $lessThan, $inclusive = false)
    {
        parent::__construct($columnName);

        $this->lessThan = $lessThan;

        $this->inclusive = $inclusive;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings[ "lessThan" ] = $this->lessThan;
        $settings[ "inclusive" ] = $this->inclusive;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self( $settings[ "columnName" ], $settings["lessThan"], $settings["inclusive"] );
    }


    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        $placeHolder = $this->detectPlaceHolder($this->lessThan);

        if (!$placeHolder) {
            $lessThan = $this->getTransformedComparisonValue($this->lessThan, $list);

            if (is_string($lessThan)) {
                $lessThan = strtolower($lessThan);
            }
        }

        foreach ($list as $item) {
            if ($placeHolder) {
                $lessThan = $this->getTransformedComparisonValue($item[$placeHolder], $list);

                if (is_string($lessThan)) {
                    $lessThan = strtolower($lessThan);
                }
            }

            $valueToTest = $item[$this->columnName];

            if (is_string($valueToTest)) {
                $valueToTest = strtolower($valueToTest);
            }

            if (
                ($valueToTest > $lessThan) ||
                ($this->inclusive == false && $valueToTest == $lessThan)
            ) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }
}