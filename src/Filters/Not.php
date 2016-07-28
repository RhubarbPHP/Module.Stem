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
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;

/**
 * Removes the records NOT selected by the filter in the constructor.
 *
 * This will in effect reverse the passed filters selection.
 */
class Not extends Filter
{
    /**
     * The filter to notFilter
     *
     * @var filter
     */
    protected $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function evaluate(Model $model)
    {
        return !$this->filter->evaluate($model);
    }

    public function checkForRelationshipIntersections(Collection $collection, $createIntersectionCallback)
    {
        $this->filter->checkForRelationshipIntersections($collection, $createIntersectionCallback);
    }
}
