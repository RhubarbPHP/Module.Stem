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

namespace Rhubarb\Stem\Schema\Relationships;

require_once __DIR__ . "/Relationship.php";

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

class OneToOne extends Relationship
{
    private $sourceColumnName;
    private $sourceModelName;
    private $targetColumnName = "";
    private $targetModelName;

    public function __construct(
        $navigationPropertyName,
        $sourceModelName,
        $sourceColumnName,
        $targetModelName,
        $targetColumnName = ""
    )
    {
        parent::__construct($navigationPropertyName);

        $this->sourceModelName = $sourceModelName;
        $this->sourceColumnName = $sourceColumnName;
        $this->targetModelName = $targetModelName;
        $this->targetColumnName = $targetColumnName;
    }

    public function getCollection()
    {
        $class = SolutionSchema::getModelClass($this->targetModelName);

        return new Collection($class);
    }

    /**
     * @return mixed
     */
    public function getSourceColumnName()
    {
        $sourceColumnName = $this->sourceColumnName;

        if ($this->sourceColumnName == "") {
            $sourceSchema = SolutionSchema::getModelSchema($this->sourceModelName);
            $sourceColumnName = $sourceSchema->uniqueIdentifierColumnName;
        }

        return $sourceColumnName;
    }

    /**
     * @return mixed
     */
    public function getTargetModelName()
    {
        return $this->targetModelName;
    }

    /**
     * @return string
     */
    public function getTargetColumnName()
    {
        $targetColumnName = $this->targetColumnName;

        if ($this->targetColumnName == "") {
            $sourceSchema = SolutionSchema::getModelSchema($this->targetModelName);
            $targetColumnName = $sourceSchema->uniqueIdentifierColumnName;
        }

        return $targetColumnName;
    }

    public function fetchFor(Model $relatedTo)
    {
        $targetModel = SolutionSchema::getModel($this->targetModelName);

        $sourceValue = $relatedTo[$this->getSourceColumnName()];

        $targetColumnName = $this->getTargetColumnName();

        if ($targetColumnName == $targetModel->UniqueIdentifierColumnName) {
            if ($sourceValue === null) {
                return null;
            }

            try {
                return SolutionSchema::getModel($this->targetModelName, $sourceValue);
            } catch (RecordNotFoundException $er) {
                return null;
            }
        } else {
            $collection = new Collection($this->targetModelName);
            $collection->filter(new Equals($targetColumnName, $sourceValue));

            if (sizeof($collection) > 0) {
                return $collection[0];
            }
        }

        return null;
    }
}