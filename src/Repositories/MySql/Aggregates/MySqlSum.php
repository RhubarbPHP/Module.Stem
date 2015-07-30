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

require_once __DIR__ . '/../../../Aggregates/Sum.php';

use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Repositories\Repository;

class MySqlSum extends Sum
{
    use MySqlAggregateTrait;

    protected static function calculateByRepository(Repository $repository, Aggregate $originalAggregate, &$relationshipsToAutoHydrate)
    {
        $columnName = str_replace('.', '`.`', $originalAggregate->aggregatedColumnName);

        if (self::canAggregateInMySql($repository, $originalAggregate->aggregatedColumnName, $relationshipsToAutoHydrate)) {
            $aliasName = $originalAggregate->getAlias();

            $originalAggregate->aggregatedByRepository = true;

            return "SUM( `{$repository->getSchema()->schemaName}`.`{$columnName}` ) AS `{$aliasName}`";
        }

        return "";
    }
}