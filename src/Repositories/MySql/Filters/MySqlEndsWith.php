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

require_once __DIR__ . "/../../../Filters/EndsWith.php";

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\EndsWith;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\SqlStatement;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlEndsWith extends EndsWith
{
    use MySqlFilterTrait;

    public static function fromGenericFilter(Filter $filter)
    {
        /**
         * @var EndsWith $filter
         */
        return new static($filter->columnName, $filter->endsWith, $filter->caseSensitive);
    }

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params
     * @return string|void
     */
    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        return $this->createColumnWhereClauseExpression(
            "LIKE",
            "%".$this->endsWith,
            $collection,
            $repository,
            $whereExpressionCollector,
            $params);
    }
}
