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

require_once __DIR__ . '/../../../Filters/OneOf.php';

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Repositories\Repository;

class MySqlOneOf extends OneOf
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  \Rhubarb\Stem\Filters\Equals|Filter   $originalFilter
     * @param  array                                 $params
     * @param  $relationshipsToAutoHydrate
     * @return string|void
     */
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$relationshipsToAutoHydrate
    ) {
    
        $columnName = $originalFilter->columnName;

        if (self::canFilter($repository, $columnName, $relationshipsToAutoHydrate)) {
            $originalFilter->filteredByRepository = true;

            $paramName = uniqid() . str_replace(".", "", $columnName);

            if (strpos($columnName, ".") === false) {
                $schema = $repository->getRepositorySchema();
                $columnName = $schema->schemaName . "`.`" . $columnName;
            } else {
                $columnName = str_replace('.', '`.`', $columnName);
            }

            if (count($originalFilter->oneOf) == 0) {
                // When a one of has nothing to filter - it should return no matches, rather than all matches.
                return " 1 = 0 ";
            }

            $oneOfParams = [];

            foreach ($originalFilter->oneOf as $key => $oneOf) {
                $key = preg_replace("/[^[:alnum:]]/", "", $key);
                $params[$paramName . $key] = $oneOf;
                $oneOfParams[] = ':' . $paramName . $key;
            }

            if (sizeof($originalFilter->oneOf) > 0) {
                return "`{$columnName}` IN ( " . implode(", ", $oneOfParams) . " )";
            }

            return " 1 = 0 ";
        }
    }
}