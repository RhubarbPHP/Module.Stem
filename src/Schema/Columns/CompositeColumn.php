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

namespace Rhubarb\Stem\Schema\Columns;

use Rhubarb\Crown\Modelling\AllPublicModelState;
use Rhubarb\Crown\Modelling\ModelState;
use Rhubarb\Stem\Models\Model;

/**
 * A column type that can present a group of 'sub columns' in a single datastructure.
 *
 * Useful for doing things like Address structures without requiring the model designer to add
 * all the backing columns and allow for an easier way to pass the Address structure around.
 */
abstract class CompositeColumn extends Column implements ModelValueInitialiserInterface
{
    public function __construct($columnName)
    {
        parent::__construct($columnName, $this->getNewContainerObject());
    }

    public function onNewModelInitialising(Model $model)
    {
        $object = $this->getNewContainerObject();
        $columns = $this->createStorageColumns();

        foreach($this->getCompositeColumnsNames() as $columnName){
            foreach($columns as $column){
                if ($column->columnName == $this->columnName.$columnName){
                    $object[$columnName] = $column->getDefaultValue();
                }
            }
        }

        $model[$this->columnName] = $object;
    }

    public function getPhpType()
    {
        return "string[]";
    }

    /**
     * Returns an array of strings; the names of the composite columns being used.
     *
     * e.g. [ "Line1", "Line2", "City", "Country" ]
     *
     * @return array
     */
    abstract protected function getCompositeColumnsNames();

    /**
     * Returns the container object used to represent the collated data.
     *
     * Uses AllPublicModelState by default which is fine unless you need a more mature
     * model object.
     */
    protected function getNewContainerObject()
    {
        return new AllPublicModelState();
    }



    public function getTransformFromRepository()
    {
        return function ($data) {
            $address = $this->getNewContainerObject();

            foreach ($this->getCompositeColumnsNames() as $column) {
                if (isset($data[$this->columnName . $column])) {
                    $address[$column] = $data[$this->columnName . $column];
                }
            }

            return $address;
        };
    }

    public function getTransformIntoRepository()
    {
        return function ($data) {
            $compositeData = $data[$this->columnName];

            // Handle occasions when the data value is an object (usually stdClass) rather than an array
            if (is_object($compositeData) && !($compositeData instanceof ModelState)) {
                $compositeData = get_object_vars($compositeData);
            }

            $exportData = [];

            foreach ($this->getCompositeColumnsNames() as $column) {
                $exportData[$this->columnName . $column] = isset($compositeData[$column]) ? $compositeData[$column] : "";
            }

            return $exportData;
        };
    }
}
