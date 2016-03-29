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

require_once __DIR__ . '/../../../Filters/Not.php';

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Repositories\Repository;

class MySqlNot extends Not
{
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$propertiesToAutoHydrate
    ) {

        /**
         * @var MySqlNot $not
         */
        $not = $originalFilter;

        $internalSql = $not->filter->filterWithRepository($repository, $params, $propertiesToAutoHydrate);

        if ($internalSql) {
            $originalFilter->filteredByRepository = true;
            return "!(" . $internalSql . ")";
        }

        return false;
    }
}
