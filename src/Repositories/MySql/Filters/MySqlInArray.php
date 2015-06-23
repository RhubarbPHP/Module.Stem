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

require_once __DIR__ . "/../../../Filters/InArray.php";

use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\InArray;
use Rhubarb\Stem\Repositories\Repository;

/**
 * Adds Mysql repository support for the InArray filter
 */
class MySqlInArray extends InArray
{
    use MySqlFilterTrait;

    /**
     * @param Repository $repository
     * @param Filter|InArray $originalFilter
     * @param array $params
     * @param                                            $relationshipsToAutoHydrate
     *
     * @return string|void
     */
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$relationshipsToAutoHydrate
    )
    {
        $columnName = $originalFilter->columnName;

        if (count($originalFilter->candidates) == 0) {
            // Make sure no rows are matched.
            return "1 = 0";
        }

        if (self::canFilter($repository, $columnName, $relationshipsToAutoHydrate)) {
            $count = count($originalFilter->candidates);

            if ($count) {
                $paramName = uniqid() . str_replace(".", "", $columnName);

                for ($i = 0; $i < $count; $i++) {
                    $params[$paramName . $i] = self::getTransformedComparisonValueForRepository($columnName,
                        $originalFilter->candidates[$i], $repository);
                }

                if (strpos($columnName, ".") === false) {
                    $schema = $repository->getSchema();
                    $columnName = $schema->schemaName . "`.`" . $columnName;
                } else {
                    $columnName = str_replace('.', '`.`', $columnName);
                }

                return "`{$columnName}` IN ( :" . implode(', :', array_keys($params)) . " )";
            }
        }
    }
}