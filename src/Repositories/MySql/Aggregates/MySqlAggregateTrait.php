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

namespace Rhubarb\Stem\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

trait MySqlAggregateTrait
{
    protected static function canAggregateInMySql(Repository $repository, $columnName)
    {
        $schema = $repository->getRepositorySchema();
        $columns = $schema->getColumns();

        if (isset($columns[$columnName])) {
            return true;
        }

        return false;
    }

    protected static function canCalculateByRepository(
        Repository $repository,
        Aggregate $originalAggregate
    )
    {
        return self::canAggregateInMySql($repository,$originalAggregate->getAggregateColumnName());
    }
}
