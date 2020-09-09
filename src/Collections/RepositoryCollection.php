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

namespace Rhubarb\Stem\Collections;

require_once __DIR__ . "/../Schema/SolutionSchema.php";

use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Exceptions\AggregateNotSupportedException;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Implements a collection of model objects that can be filtered and iterated.
 *
 * Note this class couldn't be called "List" as list is a reserved word in php
 */
class RepositoryCollection extends Collection
{
    /**
     * True if this repository can filter the collection entirely by itself.
     * 
     * @return bool
     */
    public function canBeFilteredByRepository()
    {
        // If the collection can't be filtered entirely by the repos, OR any of the aggregates
        // can't be calculated by the repos, we can't perform the intersection (as the intersection itself
        // depends critically on the rows selected).

        $filter = $this->getFilter();
        $repository = $this->getRepository();

        if ($filter && !$filter->canFilterWithRepository($this, $repository)){
            return false;
        }

        foreach($this->getAggregateColumns() as $aggregateColumn){
            if (!$aggregateColumn->canAggregateWithRepository($repository, $this)){
                return false;
            }
        }

        return true;
    }

    private $count = null;

    public function count()
    {
        // If we already have a cursor (i.e. data is already fetched) we already know how many
        // rows we have.
        if ($this->collectionCursor){
            return $this->collectionCursor->count();
        }

        if ($this->count === null){
            $this->prepareCollectionForExecution();
            $this->count = $this->getRepository()->countRowsInCollection($this);            
        }

        return $this->count;
    }

    protected function invalidate()
    {
        parent::invalidate();

        $this->count = null;
    }

    protected function createCursor()
    {
        $repository = $this->getRepository();

        return $repository->createCursorForCollection($this);
    }
}
