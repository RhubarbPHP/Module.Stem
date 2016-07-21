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

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Filters\ColumnFilter;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

/**
 * Adds a method used to determine if the filter requires auto hydration of navigation properties.
 */
trait MySqlFilterTrait
{
    /**
     * Determines if $columnName could be filtered with the MySql repository.
     *
     * If $columnName contains a dot (.) then we will check to see if we can auto hydrate the navigation
     * property.
     *
     * Note $propertiesToAutoHydrate is passed by reference as this how the filtering stack is able to
     * communication back to the repository which properties require auto hydration (if supported).
     *
     * @param  Collection $collection
     * @param  Repository $repository
     * @param  string $columnName
     * @return bool True if the MySql Repository can add this filter to its where clause.
     * @throws FilterNotSupportedException
     */
    protected static function canFilter(Collection $collection, Repository $repository, $columnName)
    {
        $schema = $repository->getRepositorySchema();
        $columns = $schema->getColumns();

        if (!isset($columns[$columnName])) {
            $aliases = $collection->getAliasedColumns();

            if (in_array($columnName, $aliases) || array_key_exists($columnName, $aliases)){
                // While not a column in the underlying table, the filter is actually on an alias from
                // an intersection or aggregate. We can handle this with a having clause so we'll
                // say "yes - we can handle this".
                return true;
            }

            return false;
        }

        return true;
    }

    protected static function getTableAlias($originalFilter, Collection $collection)
    {
        $tableAlias = null;

        $columnName = self::getRealColumnName($originalFilter, $collection);

        $aliases = $collection->getAliasedColumnsToCollection();

        if (isset($aliases[$columnName])){
            $tableAlias = $aliases[$columnName];
        }

        return $tableAlias;
    }

    protected static function getRealColumnName($originalFilter, Collection $collection)
    {
        $columnName = $originalFilter->columnName;

        $aliases = $collection->getAliasedColumns();
        if (isset($aliases[$columnName])){
            $columnName = $aliases[$columnName];
        }

        return $columnName;
    }

    protected static function createColumnWhereClauseExpression(
        $sqlOperator,
        $value,
        Collection $collection,
        Repository $repository,
        ColumnFilter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    )
    {
        $columnName = $originalFilter->columnName;

        if (self::canFilter($collection, $repository, $columnName)) {

            $aliases = $collection->getPulledUpAggregatedColumns();
            $isAlias = in_array($columnName, $aliases);

            $columnName = self::getRealColumnName($originalFilter, $collection);
            $toAlias = self::getTableAlias($originalFilter, $collection);

            if ($value === null) {
                if ($sqlOperator == "="){
                    $sqlOperator = "IS";
                }
            }

            $paramName = PdoRepository::getPdoParamName($columnName);

            $placeHolder = $originalFilter->detectPlaceHolder($value);

            if (!$placeHolder) {
                if ($value === null){
                    $paramName = "NULL";
                } else {
                    $params[$paramName] = self::getTransformedComparisonValueForRepository(
                        $columnName,
                        $value,
                        $repository
                    );
                    $paramName = ":" . $paramName;
                }
            } else {
                $paramName = "`".$collection->getUniqueReference()."`.`".$placeHolder."`";
            }

            $whereExpressionCollector->addWhereExpression(new ColumnWhereExpression($columnName, $sqlOperator . " ".$paramName, $isAlias, $toAlias));

            return true;
        }

        return false;
    }

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
        if ($originalFilter instanceof ColumnFilter) {
            return self::canFilter($collection, $repository, $originalFilter->getColumnName());
        }

        return false;
    }

    protected final static function getTransformedComparisonValueForRepository($columnName, $rawComparisonValue, Repository $repository)
    {
        $exampleObject = SolutionSchema::getModel($repository->getModelClass());

        $columnSchema = $exampleObject->getRepositoryColumnSchemaForColumnReference($columnName);

        if ($columnSchema != null) {
            // Transform the value first into model data. This function should sanitise the value as
            // the model data transforms expect inputs passed by unwary developers.
            $closure = $columnSchema->getTransformIntoModelData();

            if ($closure !== null) {
                $rawComparisonValue = $closure($rawComparisonValue);
            }

            $closure = $columnSchema->getTransformIntoRepository();

            if ($closure !== null) {
                $rawComparisonValue = $closure([$columnSchema->columnName => $rawComparisonValue]);
            }
        }

        return $rawComparisonValue;
    }
}
