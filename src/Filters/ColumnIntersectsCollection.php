<?php

/**
 * Copyright (c) 2016 RhubarbPHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Rhubarb\Stem\Filters;

use Rhubarb\Stem\Collections\Collection;

class ColumnIntersectsCollection extends Filter
{
    /**
     * @var Collection
     */
    protected $collection;

    protected $columnName;

    public function __construct($columnName, Collection $collectionToCheckForIntersection)
    {
        $this->columnName = $columnName;
        $this->collection = $collectionToCheckForIntersection;
    }

    /**
     * Implement to return an array of unique identifiers to filter from the list.
     *
     * @param  Collection $list The data list to filter.
     * @return array
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $idsToFilter = [];
        $ids = [];
        foreach($this->collection as $model){
            $ids[$model[$this->columnName]] = 1;
        }

        foreach($list as $model){
            if (!isset($ids[$model[$this->columnName]])){
                $idsToFilter[] = $model->UniqueIdentifier;
            }
        }

        return $idsToFilter;
    }
}