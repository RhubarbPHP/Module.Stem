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

namespace Rhubarb\Stem\Aggregates;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\SqlStatement;

/**
 * A base class for aggregates
 *
 * An aggregate is a way of performing an aggregate function (like sum, count etc.) on a column
 * while allowing repositories to provide repository specific optimisations.
 */
abstract class Aggregate
{
    /**
     * @var string
     */
    protected $aggregatedColumnName;

    /**
     * True if the aggregate as already been calculated.
     *
     * @var bool
     */
    public $calculated = false;

    /**
     * The groups of calculated values.
     * @var array
     */
    protected $groups = [];

    /**
     * Aliases can be auto suggested however if the aggregate is constructed with a set alias we store it here.
     * @var string
     */
    protected $alias;

    /**
     * When not empty is used as the source of column name creation.
     * @var string
     */
    protected $aliasDerivedFromColumn = "";

    public function __construct($aggregatedColumnName, $alias = "")
    {
        $this->aggregatedColumnName = $aggregatedColumnName;

        if ($alias) {
            $this->alias = $alias;
        }
    }

    public function getGroups()
    {
        return $this->groups;
    }

    final public function getAggregateColumnName()
    {
        return $this->aggregatedColumnName;
    }

    /**
     * Called when the aggregate column name needs changed.
     *
     * Used when doing intersections with dot notations.
     *
     * @param $columnName
     */
    final public function setAggregateColumnName($columnName)
    {
        $this->aggregatedColumnName = $columnName;
    }

    final public function setAliasDerivedColumn($columnName)
    {
        $this->aliasDerivedFromColumn = $columnName;
    }

    protected function getAliasDerivedColumn()
    {
        if ($this->aliasDerivedFromColumn){
            return $this->aliasDerivedFromColumn;
        }

        return $this->aggregatedColumnName;
    }

    final public function wasAggregatedByRepository()
    {
        return $this->calculated;
    }

    /**
     * Update $sqlStatement with the repository specific details for aggregating
     *
     * @param Repository $repository
     * @param SqlStatement $sqlStatement
     * @param Collection $collection
     * @param $namedParams
     */
    protected function calculateByRepository(
        Repository $repository,
        SqlStatement $sqlStatement,
        Collection $collection,
        &$namedParams
    )
    {
    }

    protected function canCalculateByRepository(
        Repository $repository,
        Collection $collection
    )
    {
        return false;
    }

    /**
     * Checks if this aggregate can be calculated using it's repository
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param Collection $collection
     * @return mixed|string
     * @internal param SqlStatement $sqlStatement
     * @internal param $namedParams
     */
    final public function canAggregateWithRepository(Repository $repository, Collection $collection)
    {
        $specificAggregate = $repository->getRepositorySpecificAggregate($this);

        if ($specificAggregate) {
            return $specificAggregate->canCalculateByRepository($repository, $collection);
        }

        return false;
    }

    /**
     * Attempts to get the repository to do the aggregation.
     *
     * If no repository support is available an empty string will be returned. Otherwise a string of data understandable
     * to the repository will be returned.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param SqlStatement $sqlStatement
     * @param Collection $collection
     * @param $namedParams
     * @return mixed|string
     */
    final public function aggregateWithRepository(Repository $repository, SqlStatement $sqlStatement, Collection $collection, &$namedParams)
    {
        $specificAggregate = $repository->getRepositorySpecificAggregate($this);

        if ($specificAggregate) {
            $specificAggregate->calculateByRepository($repository, $sqlStatement, $collection, $namedParams);

            if ($specificAggregate->calculated){
                $this->calculated = true;
            }
        }
    }

    /**
     * Implement to return an instance based upon a generic aggregate.
     * @param Aggregate $aggregate The generic aggregate to create from.
     * @return bool|Aggregate
     */
    public static function fromGenericAggregate(Aggregate $aggregate)
    {
        return false;
    }


    /**
     * Override to return a suggested alias for the aggregate.
     *
     * @return mixed
     */
    protected abstract function createAlias();



    /**
     * Returns the alias to use for the aggregate, or if one isn't defined, creates ones.
     *
     * @return mixed|string
     */
    final public function getAlias()
    {
        if ($this->alias){
            return $this->alias;
        }

        return $this->createAlias();
    }

    public function calculateByIteration(Model $model)
    {
        return null;
    }
}
