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
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
use Rhubarb\Stem\Models\Model;

class ColumnIntersectsCollection extends Equals
{
    /**
     * @var RepositoryCollection
     */
    protected $collection;

    protected $columnName;

    public function __construct($columnName, RepositoryCollection $collectionToCheckForIntersection)
    {
        $this->columnName = $columnName;
        $this->collection = $collectionToCheckForIntersection;

        parent::__construct($columnName, "@{".$collectionToCheckForIntersection->getUniqueReference().$columnName."}");
    }

    /**
     * An opportunity for implementors to create intersections on the collection.
     *
     * @param Collection $collection
     * @param $createIntersectionCallback
     * @throws CreatedIntersectionException
     * @return void
     */
    public function checkForRelationshipIntersections(Collection $collection, $createIntersectionCallback)
    {
        // While this behaviour could (and perhaps more efficiently) be achieved using `intersectWith` we instead
        // do a `joinWith` and pull the target column up so that we can filter in the normal way.
        // This allows this filter to be combined with the 'not' filter and behave as expected.
        $collection->joinWith($this->collection, $this->columnName, $this->columnName, [$this->columnName => $this->collection->getUniqueReference().$this->columnName]);
    }
}