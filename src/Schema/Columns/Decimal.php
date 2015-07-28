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

require_once __DIR__."/Column.php";

class Decimal extends Column
{
    protected $totalDigits = 8;
    protected $decimalDigits = 2;
    protected $maxValue;
    protected $minValue;

    public function __construct($columnName, $totalDigits = 8, $decimalDigits = 2, $defaultValue = null)
    {
        parent::__construct($columnName, $defaultValue);

        $this->totalDigits = $totalDigits;
        $this->decimalDigits = $decimalDigits;

        // Calculate the range of values allowed by $totalDigits
        $padding = ( $decimalDigits > 1 ) ? str_repeat('0', $decimalDigits - 1) : "";
        $this->maxValue = (float) (pow(10, $totalDigits - $decimalDigits) - ('0.' . $padding . '1'));
        $this->minValue = $this->maxValue * -1;
    }

    /**
     * @return int
     */
    public function getTotalDigits()
    {
        return $this->totalDigits;
    }

    /**
     * @return int
     */
    public function getDecimalDigits()
    {
        return $this->decimalDigits;
    }

    public function getTransformIntoModelData()
    {
        return function ($value) {
            $value = round((float)$value, $this->decimalDigits);

            // Ensure the value isn't outside the range that $this->totalDigits allows
            $value = min($this->maxValue, $value);
            $value = max($this->minValue, $value);

            return $value;
        };
    }
}