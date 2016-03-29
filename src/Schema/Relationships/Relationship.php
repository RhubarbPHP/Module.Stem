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

namespace Rhubarb\Stem\Schema\Relationships;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

abstract class Relationship
{
    public function __construct($navigationPropertyName)
    {
        $this->navigationPropertyName = $navigationPropertyName;
    }

    abstract public function fetchFor(Model $relatedTo);

    private $otherSide;

    private $navigationPropertyName;

    public function setOtherSide(Relationship $relationship)
    {
        $this->otherSide = $relationship;
    }

    public function getNavigationPropertyName()
    {
        return $this->navigationPropertyName;
    }

    /**
     * Returns a relationship object that represents the other side of the relationship
     *
     * @return Relationship
     */
    public function getOtherSide()
    {
        return $this->otherSide;
    }

    /**
     * Fetches a collection for this relationship, using the model class' find() method to allow for default filters on
     * the collection.
     *
     * @param  string $collectionClassName
     * @param  \Rhubarb\Stem\Filters\Filter|null $filter
     * @return \Rhubarb\Stem\Collections\Collection
     */
    protected function getRelationshipCollection($collectionClassName, $filter = null)
    {
        $class = SolutionSchema::getModelClass($collectionClassName);

        return $class::find($filter);
    }
}
