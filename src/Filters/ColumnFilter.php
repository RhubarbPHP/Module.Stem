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
use Rhubarb\Stem\Collections\CollectionJoin;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
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

    public function checkForRelationshipIntersections(Collection $collection, $createIntersectionCallback)
    {
        $parts = explode(".",$this->columnName);

        if (sizeof($parts) > 1){
            $this->columnName = $parts[sizeof($parts)-1];
            $intersectionsNeeded = array_slice($parts,0, sizeof($parts) - 1 );

            $collection = $createIntersectionCallback($intersectionsNeeded);
            $collection->filter($this);

            throw new CreatedIntersectionException();
        }
    }
}
