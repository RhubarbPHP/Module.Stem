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

require_once __DIR__ . "/../../../Filters/LessThan.php";

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\SqlStatement;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

/**
 * Adds MySql repository support for the Equals filter.
 */
class MySqlLessThan extends \Rhubarb\Stem\Filters\LessThan
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  \Rhubarb\Stem\Filters\Equals|Filter $originalFilter
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param  array $params
     * @return string|void
     * @internal param $relationshipsToAutoHydrate
     */
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {

        $columnName = $originalFilter->columnName;

        if (self::canFilter($repository, $columnName)) {
            $paramName = uniqid();

            $placeHolder = $originalFilter->detectPlaceHolder($originalFilter->lessThan);

            if (!$placeHolder) {
                $params[$paramName] = self::getTransformedComparisonValueForRepository(
                    $columnName,
                    $originalFilter->lessThan,
                    $repository
                );
                $paramName = ":" . $paramName;
            } else {
                $paramName = $placeHolder;
            }

            if ($originalFilter->inclusive) {
                $whereExpressionCollector->addWhereExpression(new ColumnWhereExpression($columnName, '<= '.$paramName));
            } else {
                $whereExpressionCollector->addWhereExpression(new ColumnWhereExpression($columnName, '< '.$paramName));
            }

            return true;
        }

        return false;
    }
}
