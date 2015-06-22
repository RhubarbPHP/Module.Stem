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

require_once __DIR__ . '/../../../Filters/CollectionPropertyMatches.php';

use Rhubarb\Stem\Filters\CollectionPropertyMatches;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

class MySqlCollectionPropertyMatches extends CollectionPropertyMatches
{
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$relationshipsToAutoHydrate
    )
    {
        $relationshipsToAutoHydrate[] = $originalFilter->collectionProperty;

        // Get the relationship
        $relationships = SolutionSchema::getAllRelationshipsForModel($repository->getModelClass());

        /**
         * @var OneToMany $relationship
         */
        $relationship = $relationships[$originalFilter->collectionProperty];

        $columnName = $relationship->getNavigationPropertyName() . "`.`" . $originalFilter->columnName;

        $paramName = uniqid() . str_replace("`.`", "", $columnName);

        $params[$paramName] = $originalFilter->equalTo;

        $originalFilter->filteredByRepository = true;

        return "`{$columnName}` = :{$paramName}";
    }
}