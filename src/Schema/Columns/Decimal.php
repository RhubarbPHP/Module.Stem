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

    public function __construct($columnName, $totalDigits = 8, $decimalDigits = 2, $defaultValue = null)
    {
        parent::__construct($columnName, $defaultValue);

        $this->totalDigits = $totalDigits;
        $this->decimalDigits = $decimalDigits;
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
}