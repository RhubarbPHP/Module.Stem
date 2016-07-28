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

namespace Rhubarb\Stem\Repositories\MySql\Filters;

require_once __DIR__ . "/../../../Filters/Group.php";

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\AndExpression;
use Rhubarb\Stem\Sql\OrExpression;
use Rhubarb\Stem\Sql\SqlStatement;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlGroup extends Group
{
    /**
     * Return true if the repository can handle this filter.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param Filter $originalFilter
     * @return bool
     */
    protected static function doCanFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter
    ){
        /**
         * @var Filter[] $filters
         */
        $filters = $originalFilter->getFilters();

        foreach ($filters as $filter) {
            if (!$filter->canFilterWithRepository($collection, $repository)){
                return false;
            }
        }

        return true;
    }

    protected static function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {

        switch ($originalFilter->booleanType){
            case "OR":
                $group = new OrExpression();
                break;
            default:
                $group = new AndExpression();
                break;
        }

        /**
         * @var Filter[] $filters
         */
        $filters = $originalFilter->getFilters();
        $filterSql = [];

        foreach ($filters as $filter) {
            $filter->filterWithRepository($collection, $repository, $group, $params);
        }

        if (sizeof($group->whereExpressions) > 0) {
            $whereExpressionCollector->addWhereExpression($group);
        }
    }
}
