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

use Rhubarb\Crown\DateTime\RhubarbDate;

require_once __DIR__ . "/Column.php";

class Date extends Column
{
    public function getTransformIntoModelData()
    {
        return function ($data) {
            return new RhubarbDate($data);
        };
    }

    public function getTransformFromModelData()
    {
        // Ensure that dates get cloned on the way out or people will end up modifying models by mistake.
        return function ($data) {
            if ($data instanceof \DateTime) {
                return clone $data;
            }

            return $data;
        };
    }
}
