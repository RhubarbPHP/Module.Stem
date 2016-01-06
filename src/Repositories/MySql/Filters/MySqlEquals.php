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

require_once __DIR__ . "/../../../Filters/Equals.php";

use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;

class MySqlEquals extends Equals
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  Equals|Filter                         $originalFilter
     * @param  array                                 $params
     * @param  array                                 $relationshipsToAutoHydrate
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
            $queryColumnName = $columnName;
            if (strpos($queryColumnName, ".") === false) {
                $schema = $repository->getRepositorySchema();
                $queryColumnName = $schema->schemaName . "`.`" . $queryColumnName;
            } else {
                $queryColumnName = str_replace('.', '`.`', $queryColumnName);
            }

            if ($originalFilter->equalTo === null) {
                return "`{$queryColumnName}` IS NULL";
            }

            $paramName = uniqid() . str_replace(".", "", $columnName);

            $originalFilter->filteredByRepository = true;

            $placeHolder = $originalFilter->detectPlaceHolder($originalFilter->equalTo);

            if (!$placeHolder) {
                $params[$paramName] = $params[$paramName] = self::getTransformedComparisonValueForRepository(
                    $columnName,
                    $originalFilter->equalTo, $repository
                );;
                $paramName = ":" . $paramName;
            } else {
                $paramName = $placeHolder;
            }

            return "`{$queryColumnName}` = {$paramName}";
        }
    }
}
