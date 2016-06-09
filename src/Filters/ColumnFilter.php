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

namespace Rhubarb\Stem\Filters;

require_once __DIR__ . '/Filter.php';

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\Intersection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * A base class for all filters that match a value against a single column in some way.
 */
abstract class ColumnFilter extends Filter
{
    protected $columnName;

    public function __construct($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return mixed
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["columnName"] = $this->columnName;

        return $settings;
    }


    /**
     * Converts the comparison value used in the constructor to one which can be compared against that returned
     * by the relevant model.
     *
     * @param $rawComparisonValue
     * @param Model $model
     * @return mixed
     */
    final protected function getTransformedComparisonValue($rawComparisonValue, Model $model)
    {
        $columnSchema = $model->getRepositoryColumnSchemaForColumnReference($this->columnName);

        if ($columnSchema != null) {
            $closure = $columnSchema->getTransformIntoModelData();

            if ($closure !== null) {
                $rawComparisonValue = $closure($rawComparisonValue);
            }
        }

        return $rawComparisonValue;
    }

    public function checkForRelationshipIntersections(Collection $collection)
    {
        $parts = explode(".",$this->columnName);
        if (sizeof($parts) > 1){

            $columnName = $parts[sizeof($parts)-1];
            $relationships = SolutionSchema::getAllRelationshipsForModel($collection->getModelClassName());
            
            for($x = 0; $x < sizeof($parts) - 1; $x++){
                $relationshipPropertyName = $parts[$x];

                if (!isset($relationships[$relationshipPropertyName])){
                    throw new FilterNotSupportedException("The column ".$this->columnName." couldn't be expanded to intersections");
                }

                $relationship = $relationships[$relationshipPropertyName];

                if ($relationship instanceof OneToMany) {
                    $targetModel = $relationship->getTargetModelName();
                    $parentColumn = $relationship->getSourceColumnName();
                    $childColumn = $relationship->getTargetColumnName();

                    $collection->intersectWith(
                        new RepositoryCollection($targetModel),
                        $parentColumn,
                        $childColumn,
                        [
                            $columnName
                        ]
                    );
                }
            }

            $this->columnName = $parts[sizeof($parts)-1];
        }
    }
}
