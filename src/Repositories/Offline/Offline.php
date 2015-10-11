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

namespace Rhubarb\Stem\Repositories\Offline;

require_once __DIR__ . "/../Repository.php";

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\AutoIncrement;

class Offline extends Repository
{
    private $autoNumberCount = 0;

    protected function onObjectSaved(Model $object)
    {
        if ($object->isNewRecord()) {

            $columnName = $object->UniqueIdentifierColumnName;
            if($object->getSchema()->getColumns()[$columnName] instanceof AutoIncrement)
            {
                // Assign an auto number as a unique identifier.
                $this->autoNumberCount++;
                $object->UniqueIdentifier = $this->autoNumberCount;
            }
        }

        parent::onObjectSaved($object);
    }

    public function clearObjectCache()
    {
        parent::clearObjectCache();

        $this->autoNumberCount = 0;
    }
}
