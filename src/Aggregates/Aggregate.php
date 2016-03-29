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
use Rhubarb\Stem\Repositories\Repository;

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

    public function __construct($aggregatedColumnName)
    {
        $this->aggregatedColumnName = $aggregatedColumnName;
    }

    /**
     * Returns the column to be used when calcuating by iteration
     *
     * Provided in case this aggregate is working on a relationship.
     */
    protected function getModelColumnForIteration()
    {
        $parts = explode(".", $this->aggregatedColumnName);

        return $parts[sizeof($parts) - 1];
    }


    final public function getAggregateColumnName()
    {
        return $this->aggregatedColumnName;
    }

    final public function wasAggregatedByRepository()
    {
        return $this->aggregatedByRepository;
    }

    protected static function calculateByRepository(
        Repository $repository,
        Aggregate $originalAggregate,
        &$relationshipsToAutoHydrate
    ) {


    }

    /**
     * Attempts to get the repository to do the aggregation.
     *
     * If no repository support is available an empty string will be returned. Otherwise a string of data understandable
     * to the repository will be returned.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  $relationshipsToAutoHydrate
     * @return mixed|string
     */
    final public function aggregateWithRepository(Repository $repository, &$relationshipsToAutoHydrate)
    {
        $reposName = basename(str_replace("\\", "/", get_class($repository)));

        // Get the repository specific implementation of the aggregate.
        $className = "\Rhubarb\Stem\Repositories\\" . $reposName . "\\Aggregates\\" . $reposName . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            return call_user_func_array(
                $className . "::calculateByRepository",
                [$repository, $this, &$relationshipsToAutoHydrate]
            );
        }

        return "";
    }

    abstract public function getAlias();

    public function calculateByIteration(Collection $collection)
    {
        return null;
    }
}
