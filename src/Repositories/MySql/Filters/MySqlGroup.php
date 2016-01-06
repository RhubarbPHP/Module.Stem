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

require_once __DIR__ . "/../../../Filters/Group.php";

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Repositories\Repository;

class MySqlGroup extends Group
{
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$propertiesToAutoHydrate
    ) {
    
        $filters = $originalFilter->getFilters();
        $filterSql = [];

        foreach ($filters as $filter) {
            $thisFilterSql = $filter->filterWithRepository($repository, $params, $propertiesToAutoHydrate);

            if ($thisFilterSql != "") {
                $filterSql[] = $thisFilterSql;
            }
        }

        if (sizeof($filterSql) > 0) {
            return "( " . implode(" " . $originalFilter->booleanType . " ", $filterSql) . " )";
        }

        return "";
    }
}
