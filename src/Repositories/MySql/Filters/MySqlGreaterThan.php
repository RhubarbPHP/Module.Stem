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

require_once __DIR__ . "/../../../Filters/GreaterThan.php";

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\Repository;

/**
 * Adds MySql repository support for the Equals filter.
 */
class MySqlGreaterThan extends GreaterThan
{
    use MySqlFilterTrait;

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param  \Rhubarb\Stem\Repositories\Repository $repository
     * @param  \Rhubarb\Stem\Filters\Equals|Filter   $originalFilter
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
            $paramName = uniqid() . str_replace(".", "", $columnName);

            $placeHolder = $originalFilter->detectPlaceHolder($originalFilter->greaterThan);

            $originalFilter->filteredByRepository = true;

            if (!$placeHolder) {
                $params[$paramName] = self::getTransformedComparisonValueForRepository(
                    $columnName,
                    $originalFilter->greaterThan, $repository
                );
                $paramName = ":" . $paramName;
            } else {
                $paramName = $placeHolder;
            }

            if (strpos($columnName, ".") === false) {
                $schema = $repository->getRepositorySchema();
                $columnName = $schema->schemaName . "`.`" . $columnName;
            } else {
                $columnName = str_replace('.', '`.`', $columnName);
            }

            if ($originalFilter->inclusive) {
                return "`{$columnName}` >= {$paramName}";
            } else {
                return "`{$columnName}` > {$paramName}";
            }
        }

        parent::doFilterWithRepository($repository, $originalFilter, $object, $params);
    }
}
