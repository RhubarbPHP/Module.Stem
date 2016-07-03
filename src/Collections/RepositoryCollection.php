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
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Implements a collection of model objects that can be filtered and iterated.
 *
 * Note this class couldn't be called "List" as list is a reserved word in php
 */
class RepositoryCollection extends Collection
{
    public function canBeFilteredByRepository()
    {
        $filter = $this->getFilter();

        if (!$filter){
            return true;
        }

        return $filter->canFilterWithRepository($this, $this->getRepository());
    }

    protected function createCursor()
    {
        $repository = $this->getRepository();

        return $repository->createCursorForCollection($this);
    }
}
