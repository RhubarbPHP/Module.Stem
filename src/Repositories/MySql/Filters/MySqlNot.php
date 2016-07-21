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

require_once __DIR__ . '/../../../Filters/Not.php';

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\AndExpression;
use Rhubarb\Stem\Sql\NotExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlNot extends Not
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  \Rhubarb\Stem\Filters\Equals|Filter $originalFilter
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param  array $params
     * @return string|void
     * @internal param $relationshipsToAutoHydrate
     */
    protected static function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {

        $interceptingCollector = new AndExpression();
        /**
         * @var MySqlNot $not
         */
        $not = $originalFilter;

        $not->filter->filterWithRepository($collection, $repository, $interceptingCollector, $params);

        if (count($interceptingCollector->whereExpressions)==1){

            $expression = $interceptingCollector->whereExpressions[0];
            $whereExpressionCollector->addWhereExpression(new NotExpression($expression));

            return true;
        }

        return false;
    }
}
