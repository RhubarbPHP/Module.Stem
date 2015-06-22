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

require_once __DIR__ . "/../../../Filters/Contains.php";

use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;

class MySqlContains extends Contains
{
    use MySqlFilterTrait;

    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$propertiesToAutoHydrate
    )
    {
        $columnName = $originalFilter->columnName;

        if (self::canFilter($repository, $columnName, $propertiesToAutoHydrate)) {
            $paramName = uniqid() . str_replace(".", "", $columnName);

            $params[$paramName] = "%" . $originalFilter->contains . "%";

            $originalFilter->filteredByRepository = true;

            if (strpos($columnName, ".") === false) {
                $schema = $repository->getSchema();
                $columnName = $schema->schemaName . "`.`" . $columnName;
            } else {
                $columnName = str_replace('.', '`.`', $columnName);
            }

            return "`{$columnName}` LIKE :{$paramName}";
        }

        parent::doFilterWithRepository($repository, $originalFilter, $object, $params, $propertiesToAutoHydrate);
    }
}