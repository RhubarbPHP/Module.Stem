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

/**
 * Schema information about a column.
 */
class Column
{
    /**
     * The name of the column
     *
     * @var
     */
    public $columnName;

    /**
     * The default value for the column.
     *
     * Defaults to null which normally means no default.
     *
     * @var
     */
    public $defaultValue = null;

    public function __construct($columnName, $defaultValue = null)
    {
        $this->columnName = $columnName;
        $this->defaultValue = $defaultValue;
    }

    /**
     * Return the definition string needed to update the back end storage schema to match.
     *
     * @return string
     */
    public function getDefinition()
    {
        return "";
    }

    /**
     * Optionally returns a Closure that can transform model data as it is set by the user of the model.
     *
     * We use closures here for speed - we want to cache these methods into an array on the Repository so that
     * we don't have to call these methods for every set and get on the model - only where the column has
     * defined these values.
     *
     * @return null
     */
    public function getTransformIntoModelData()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is returned to the user of the model.
     *
     * @see getTransformIntoModelData()
     * @return null
     */
    public function getTransformFromModelData()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is received from the repository
     *
     * @see getTransformIntoModelData()
     * @return null
     */
    public function getTransformFromRepository()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is sent to the repository
     *
     * @see getTransformIntoModelData()
     * @return null
     */
    public function getTransformIntoRepository()
    {
        return null;
    }
}
