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

use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

class OneToMany extends Relationship
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

    /**
     * @return mixed
     */
    public function getSourceColumnName()
    {
        $sourceColumnName = $this->sourceColumnName;

        if ($sourceColumnName == "") {
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

        if ($targetColumnName == "") {
            $targetColumnName = $this->getSourceColumnName();
        }

        return $targetColumnName;
    }

    /**
     * Returns the unfiltered collection on the many side of the relationship.
     *
     * Used for populating drop down lists etc.
     *
     * @return \Rhubarb\Stem\Collections\Collection
     */
    public function getCollection()
    {
        return $this->getRelationshipCollection($this->targetModelName);
    }

    public function fetchFor(Model $relatedTo)
    {
        return $this->getRelationshipCollection(
            $this->targetModelName,
            new Equals(
                $this->getTargetColumnName(),
                $relatedTo[$this->getSourceColumnName()]
            )
        );
    }
}