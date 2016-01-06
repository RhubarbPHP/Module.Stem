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

use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

trait MySqlAggregateTrait
{
    protected static function canAggregateInMySql(Repository $repository, $columnName, &$relationshipsToAutoHydrate)
    {
        $schema = $repository->getRepositorySchema();
        $columns = $schema->getColumns();

        if (isset($columns[$columnName])) {
            return true;
        }

        if (strpos($columnName, ".") !== false) {
            // If the column name contains a dot, the part before the dot is the name of a relationship to another model, or the name of this model's table
            list($tableName, $columnName) = explode(".", $columnName, 2);

            if ($tableName == $schema->schemaName) {
                return true;
            }

            // It wasn't the name of this model's table, so it must be the name of a relationship
            $relationship = $tableName;
            $relationships = SolutionSchema::getAllRelationshipsForModel($repository->getModelClass());

            // Check for the name being that of a relationship
            if (isset($relationships[$relationship]) && ($relationships[$relationship] instanceof OneToMany)) {
                $targetModelName = $relationships[$relationship]->getTargetModelName();
                $targetSchema = SolutionSchema::getModelSchema($targetModelName);

                $targetColumns = $targetSchema->getColumns();

                // Check for the column name in the schema of the related model
                if (isset($targetColumns[$columnName])) {
                    $relationshipsToAutoHydrate[] = $relationship;
                    return true;
                }
            }
        }

        return false;
    }
}