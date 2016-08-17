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

require_once __DIR__ . "/../../../Filters/GreaterThan.php";

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\SqlStatement;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

/**
 * Adds MySql repository support for the Equals filter.
 */
class MySqlGreaterThan extends GreaterThan
{
    use MySqlFilterTrait;

    public static function fromGenericFilter(Filter $filter)
    {
        /**
         * @var GreaterThan $filter
         */
        return new static($filter->columnName, $filter->greaterThan, $filter->inclusive);
    }
    
    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param  array $params
     * @return string|void
     * @internal param $relationshipsToAutoHydrate
     */
    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        return $this->createColumnWhereClauseExpression(
            ($this->inclusive) ? ">=" : ">",
            $this->greaterThan,
            $collection,
            $repository,
            $whereExpressionCollector,
            $params);
    }
}
