<?php
/*
 *	Copyright 2017 RhubarbPHP
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

use Rhubarb\Stem\Models\Model;

class ListContains extends ColumnFilter
{
    /**
     * @var null|array
     */
    public $contains = null;

    public function __construct($columnName, array $contains)
    {
        parent::__construct($columnName);

        $this->contains = $contains;
    }

    public function evaluate(Model $model)
    {
        $placeHolder = $this->detectPlaceHolder($this->contains);

        if ($placeHolder) {
            $contains = $model[$placeHolder];
        } else {
            $contains = $this->contains;
        }

        $contains = $this->getTransformedComparisonValue($contains, $model);

        foreach ($contains as $contain) {
            if (in_array($contain, $model[$this->columnName]) === false) {
                return true;
            }
        }

        return false;
    }
}
