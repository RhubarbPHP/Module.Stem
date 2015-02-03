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

namespace Rhubarb\Stem\Aggregates;

require_once __DIR__ . "/Aggregate.php";

use Rhubarb\Stem\Collections\Collection;

class Count extends Aggregate
{
    public function getAlias()
    {
        return "CountOf" . str_replace(".", "", $this->aggregatedColumnName);
    }

    public function calculateByIteration(Collection $collection)
    {
        // If the list hasn't been fetched, sizeof() will still calculate it with an Aggregate,
        // so we need for force the list to be fetched now so the sizeof() can calculate by iteration
        $collection->fetchList();
        return sizeof($collection);
    }
}