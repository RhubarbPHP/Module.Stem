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
 * Data filter used to combine other data filters together.
 *
 * Can match either ALL or ANY of the filters by setting the boolean type to AND or OR
 */
class Group extends Filter
{
    /**
     * The array of the Filters to Use for this filter
     *
     * @var Filter[]
     */
    private $filters = [];

    /**
     * The boolean type for this filter
     * - should be one of AND OR
     * @var string
     */
    protected $booleanType = "And";


    public function __construct($booleanType = "And", $filters = [])
    {
        $this->booleanType = $booleanType;

        $this->filters = $filters;
    }

    /**
     * Return all the filters as an array (combined with any children sub filters)
     *
     * @return array
     */
    public function getAllFilters()
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $filters = array_merge($filters, $filter->getAllFilters());
        }

        return $filters;
    }

    public function wasFilteredByRepository()
    {
        $result = true;

        foreach ($this->filters as $filter) {
            if (!$filter->wasFilteredByRepository()) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Adds one or more filter objects to the filter collection.
     *
     * @throws \Exception
     */
    public function addFilters()
    {
        foreach (func_get_args() as $filter) {
            if (is_a($filter, 'Rhubarb\Stem\Filters\Filter')) {
                $this->filters[] = $filter;
            } else {
                throw new \Exception('Non filter object added to Group filter');
            }
        }
    }

    /**
     * Returns the list of filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    public function evaluate(Model $model)
    {
        $or = true;

        foreach ($this->filters as $filter) {
            if ($filter->filteredByRepository) {
                continue;
            }

            $subFiltered = $filter->evaluate($model);

            if (strtoupper($this->booleanType) == "AND") {
                if ($subFiltered){
                    // When ANDing, if any of the filters would remove it, we need to remove. In other words
                    // all the filters must want to keep the model.
                    return true;
                }
            } else {
                if (!$subFiltered){
                    return false;
                }
            }
        }

        if (strtoupper($this->booleanType) == "AND") {
            return false;
        } else {
            return true;
        }
    }

    public function setFilterValuesOnModel(Model $model)
    {
        if (strtoupper($this->booleanType) == "OR") {
            return;
        }

        foreach ($this->filters as $filter) {
            $filter->setFilterValuesOnModel($model);
        }
    }

    public function checkForRelationshipIntersections(Collection $collection)
    {
        foreach($this->filters as $filter){
            $filter->checkForRelationshipIntersections($collection);
        }
    }
}
