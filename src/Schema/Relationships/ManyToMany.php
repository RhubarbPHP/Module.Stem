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

require_once __DIR__ . '/Relationship.php';

use Rhubarb\Stem\Filters\CollectionPropertyMatches;
use Rhubarb\Stem\Models\Model;

class ManyToMany extends Relationship
{
    private $leftModelName;
    private $leftColumnName;
    private $joiningModelName;
    private $joiningLeftColumnName;
    private $joiningRightColumnName;
    private $rightModelName;
    private $rightColumnName;

    public function __construct(
        $navigationPropertyName,
        $leftModelName,
        $leftColumnName,
        $joiningModelName,
        $joiningLeftColumnName,
        $joiningRightColumnName,
        $rightModelName,
        $rightColumnName
    )
    {
        parent::__construct($navigationPropertyName);

        $this->leftColumnName = $leftColumnName;
        $this->leftModelName = $leftModelName;
        $this->joiningModelName = $joiningModelName;
        $this->joiningLeftColumnName = $joiningLeftColumnName;
        $this->joiningRightColumnName = $joiningRightColumnName;
        $this->rightColumnName = $rightColumnName;
        $this->rightModelName = $rightModelName;
    }

    public function getRightModelName()
    {
        return $this->rightModelName;
    }

    public function fetchFor(Model $relatedTo)
    {
        return $this->getRelationshipCollection(
            $this->rightModelName,
            new CollectionPropertyMatches(
                $this->getOtherSide()->getNavigationPropertyName() . 'Raw',
                $this->joiningLeftColumnName,
                $relatedTo->UniqueIdentifier
            )
        );
    }
}