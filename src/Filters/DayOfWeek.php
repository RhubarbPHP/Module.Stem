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
use Rhubarb\Stem\Collections\Collection;

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

    /**
     * Implement to return an array of unique identifiers to filter from the list.
     *
     * @param Collection $list The data list to filter.
     * @return array
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $idsToFilter = [];

        foreach ($list as $item) {
            $filter = false;

            if (!$item[$this->columnName] instanceof RhubarbDateTime) {
                $filter = true;
            } else {
                if (!in_array($item[$this->columnName]->format("N") - 1, $this->validDays)) {
                    $filter = true;
                }
            }

            if ($filter) {
                $idsToFilter[] = $item->UniqueIdentifier;
            }
        }

        return $idsToFilter;
    }
}