<?php
/*
 *	Copyright 2017 RhubarbPHP
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

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\ListContains;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlListContains extends ListContains
{
    use MySqlFilterTrait;

    public static function fromGenericFilter(Filter $filter)
    {
        /**
         * @var ListContains $filter
         */
        return new static($filter->columnName, $filter->contains);
    }

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params
     * @return bool
     */
    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        $filtered = true;

        foreach ($this->contains as $contain) {
            $currentFiltered = $this->createColumnWhereClauseExpression(
                "LIKE",
                [$contain],
                $collection,
                $repository,
                $whereExpressionCollector,
                $params);

            $keys = array_keys($params);

            $end = end($keys);

            $params[$end] = "%{$params[$end]}%";

            if ($filtered && !$currentFiltered) {
                $filtered = false;
            }
        }


        return $filtered;
    }
}
