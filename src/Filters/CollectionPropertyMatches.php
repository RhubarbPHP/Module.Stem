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

require_once __DIR__ . '/Equals.php';

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

class CollectionPropertyMatches extends ColumnFilter
{
    protected $matchesFilter;
    protected $equalTo;
    protected $collectionProperty;

    public function __construct($collectionProperty, $columnName, $equalTo)
    {
        parent::__construct($columnName);
        $this->equalTo = $equalTo;
        $this->collectionProperty = $collectionProperty;
    }

    public function setFilterValuesOnModel(Model $model)
    {
        // Create a row in the intermediate collection so that if the filter was ran again the model
        // would now qualify.
        $relationships = SolutionSchema::getAllRelationshipsForModel("\\" . get_class($model));

        /**
         * @var OneToMany $relationship
         */
        $relationship = $relationships[$this->collectionProperty];
        $modelName = $relationship->getTargetModelName();

        $newModel = SolutionSchema::getModel($modelName);
        $newModel[$model->UniqueIdentifierColumnName] = $model->UniqueIdentifier;
        $newModel[$this->columnName] = $this->equalTo;
        $newModel->save();

        return $newModel;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["collectionProperty"] = $this->collectionProperty;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["collectionProperty"], $settings["columnName"], $settings["equalTo"]);
    }

    public function checkForRelationshipIntersections(Collection $collection, $createIntersectionCallback)
    {
        $collection = $createIntersectionCallback([$this->collectionProperty]);
        $collection->filter(new Equals($this->columnName, $this->equalTo));

        throw new CreatedIntersectionException();
    }

    /**
     * Chooses whether to remove the model from the list or not
     *
     * Returns true to remove it, false to keep it.
     *
     * @param Model $model
     * @return array
     */
    public function evaluate(Model $model)
    {
        // Not used by this filter.
    }
}
