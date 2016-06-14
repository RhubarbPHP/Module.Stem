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
     * Set to true by a repository specific implementation of the aggregate to indicate it was able to offload this to
     * the repository.
     *
     * @var bool
     */
    protected $aggregatedByRepository = false;

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

    public function __construct($aggregatedColumnName, $alias = "")
    {
        $this->aggregatedColumnName = $aggregatedColumnName;

        if ($alias){
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

    final public function wasAggregatedByRepository()
    {
        return $this->aggregatedByRepository;
    }

    protected static function calculateByRepository(
        Repository $repository,
        Aggregate $originalAggregate,
        SqlStatement $sqlStatement,
        &$namedParams
    ) {


    }

    /**
     * Attempts to get the repository to do the aggregation.
     *
     * If no repository support is available an empty string will be returned. Otherwise a string of data understandable
     * to the repository will be returned.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param SqlStatement $sqlStatement
     * @param $namedParams
     * @return mixed|string
     */
    final public function aggregateWithRepository(Repository $repository, SqlStatement $sqlStatement, &$namedParams)
    {
        $reposName = basename(str_replace("\\", "/", get_class($repository)));

        // Get the repository specific implementation of the aggregate.
        $className = "\Rhubarb\Stem\Repositories\\" . $reposName . "\\Aggregates\\" . $reposName . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            return call_user_func_array(
                $className . "::calculateByRepository",
                [$repository, $this, $sqlStatement, &$namedParams]
            );
        }

        return "";
    }

    /**
     * Override to return a suggested alias for the aggregate.
     * 
     * @return mixed
     */
    protected abstract function createAlias();

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
