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

require_once __DIR__ . '/../../../Aggregates/Count.php';

use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\GroupExpression;
use Rhubarb\Stem\Sql\SelectColumn;
use Rhubarb\Stem\Sql\SelectExpression;
use Rhubarb\Stem\Sql\SqlStatement;

class MySqlCount extends Count
{
    use MySqlAggregateTrait;

    protected static function calculateByRepository(Repository $repository, Aggregate $originalAggregate, SqlStatement $sqlStatement, &$namedParams)
    {
        $aliasName = $originalAggregate->getAlias();

        $originalAggregate->calculated = true;

        $sqlStatement->columns[] = new SelectExpression("COUNT(*) AS `{$aliasName}`");
    }
}
