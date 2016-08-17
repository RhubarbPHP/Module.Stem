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

require_once __DIR__ . '/../../../Filters/OneOf.php';

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\LiteralWhereExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlOneOf extends OneOf
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param Filter $originalFilter
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params
     * @return string|void
     */
    protected static function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {

        $columnName = $originalFilter->columnName;

        if (self::canFilter($collection, $repository, $columnName)) {

            $aliases = $collection->getPulledUpAggregatedColumns();
            $isAlias = in_array($columnName, $aliases);

            $columnName = self::getRealColumnName($originalFilter, $collection);
            $toAlias = self::getTableAlias($originalFilter, $collection);

            if (count($originalFilter->oneOf) == 0) {
                // When a one of has nothing to filter - it should return no matches, rather than all matches.
                $whereExpressionCollector->addWhereExpression(
                    new LiteralWhereExpression(
                        "1=0")
                );

                return " 1 = 0 ";
            }

            $oneOfParams = [];
            $paramName = uniqid() . $columnName;

            foreach ($originalFilter->oneOf as $key => $oneOf) {
                $key = preg_replace("/[^[:alnum:]]/", "", $key);
                $params[$paramName . $key] = $oneOf;
                $oneOfParams[] = ':' . $paramName . $key;
            }

            $whereExpressionCollector->addWhereExpression(new ColumnWhereExpression($columnName, " IN ( " . implode(", ", $oneOfParams) . " )", $isAlias, $toAlias));
        }
    }
}
