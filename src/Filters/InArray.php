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

require_once __DIR__ . "/Filter.php";

use Rhubarb\Stem\Collections\Collection;

/**
 * Filter by matching the column to one of the items in an array of candidates
 */
class InArray extends ColumnFilter
{
    protected $candidates;

    public function __construct($columnName, $candidates = [])
    {
        parent::__construct($columnName);

        $this->candidates = array_values($candidates);
    }

    /**
     * Implement to return an array of unique identifiers to filter from the list.
     * @param \Rhubarb\Stem\Collections\Collection $list The data list to filter.
     * @return array
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = array();

        foreach ($list as $item) {
            $columnValue = $item[$this->columnName];

            if (!in_array($columnValue, $this->candidates)) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }
}
