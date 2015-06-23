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
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 *
 *
 * @package Gcd\Tests
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class CollectionPropertyMatches extends Equals
{
    protected $matchesFilter;
    protected $collectionProperty;

    public function __construct($collectionProperty, $columnName, $equalTo)
    {
        parent::__construct($columnName, $equalTo);

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

    /**
     * Implement to return an array of unique identifiers to filter from the list.
     *
     * @param Collection $list The data list to filter.
     * @return array
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        foreach ($list as $item) {
            $collection = $item[$this->collectionProperty];

            $filter = new Group("AND");
            $filter->addFilters(
                $collection->getFilter(),
                new Equals($this->columnName, $this->equalTo));

            $collection->filter($filter);

            if (!sizeof($collection)) {
                $ids[] = $item->UniqueIdentifier;
            }
        }

        return $ids;
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings[ "collectionProperty" ] = $this->collectionProperty;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self( $settings["collectionProperty"], $settings[ "columnName" ], $settings["equalTo"] );
    }
}