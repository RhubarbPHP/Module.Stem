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

use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Adds a method used to determine if the filter requires auto hydration of navigation properties.
 */
trait MySqlFilterTrait
{
    /**
     * Determines if $columnName could be filtered with the MySql repository.
     *
     * If $columnName contains a dot (.) then we will check to see if we can auto hydrate the navigation
     * property.
     *
     * Note $propertiesToAutoHydrate is passed by reference as this how the filtering stack is able to
     * communication back to the repository which properties require auto hydration (if supported).
     *
     * @param  Repository $repository
     * @param  string $columnName
     * @return bool True if the MySql Repository can add this filter to its where clause.
     * @throws FilterNotSupportedException
     */
    protected static function canFilter(Repository $repository, $columnName)
    {
        $schema = $repository->getRepositorySchema();
        $columns = $schema->getColumns();

        if (!isset($columns[$columnName])) {
            return false;
        }

        return true;
    }

    protected final static function getTransformedComparisonValueForRepository($columnName, $rawComparisonValue, Repository $repository)
    {
        $exampleObject = SolutionSchema::getModel($repository->getModelClass());

        $columnSchema = $exampleObject->getRepositoryColumnSchemaForColumnReference($columnName);

        if ($columnSchema != null) {
            // Transform the value first into model data. This function should sanitise the value as
            // the model data transforms expect inputs passed by unwary developers.
            $closure = $columnSchema->getTransformIntoModelData();

            if ($closure !== null) {
                $rawComparisonValue = $closure($rawComparisonValue);
            }

            $closure = $columnSchema->getTransformIntoRepository();

            if ($closure !== null) {
                $rawComparisonValue = $closure([$columnSchema->columnName => $rawComparisonValue]);
            }
        }

        return $rawComparisonValue;
    }
}
