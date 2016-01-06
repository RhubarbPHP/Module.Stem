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

    public function getPhpType()
    {
        return "string";
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
     * Returns an array of column objects capable of supplying the schema details for this column.
     *
     * Normally a column can specify it's own schema, however sometimes a column extends another column type
     * simply to add some transforms, for example Json extends LongString and adds json encoding and decoding.
     * However for this column to be supported in all repository types you would need to create a separate
     * repository specific extension of the class for every repository.
     *
     * By overriding this function you can delegate the storage of the raw data to another simpler column
     * type that has already had the repository specific instances created.
     *
     * @return Column[]
     */
    public function createStorageColumns()
    {
        return [$this];
    }

    /**
     * Returns an array of named columns needed for storage.
     *
     * @see    createStorageColumns()
     * @return Column[]
     */
    public function getStorageColumns()
    {
        $columns = $this->createStorageColumns();

        $namedColumns = [];

        foreach ($columns as $column) {
            $namedColumns[$column->columnName] = $column;
        }

        return $namedColumns;
    }

    /**
     * Returns a repository specific version of this column if one is available.
     *
     * If no repository specific version is available $this is passed back.
     *
     * @param  string $repositoryClassName
     * @return Column
     */
    public final function getRepositorySpecificColumn($repositoryClassName)
    {
        $reposName = basename(str_replace("\\", "/", $repositoryClassName));

        // Get the provider specific implementation of the column.
        $className = '\Rhubarb\Stem\Repositories\\' . $reposName . '\Schema\Columns\\' . $reposName . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            $superType = call_user_func_array($className . "::fromGenericColumnType", [$this]);

            // fromGenericColumnType could return false if it doesn't supply any schema details.
            if ($superType !== false) {
                return $superType;
            }
        }

        return $this;
    }

    /**
     * Returns an instance of the column using a generic column to provide the settings.
     *
     * You should override this if your column is a repository specific implementation.
     *
     * @param  Column $genericColumn
     * @return bool|Column Returns the repository specific column or false if the column doesn't support that.
     */
    protected static function fromGenericColumnType(Column $genericColumn)
    {
        return false;
    }


    /**
     * Optionally returns a Closure that can transform model data as it is set by the user of the model.
     *
     * We use closures here for speed - we want to cache these methods into an array on the Repository so that
     * we don't have to call these methods for every set and get on the model - only where the column has
     * defined these values.
     *
     * @return null|callable
     */
    public function getTransformIntoModelData()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is returned to the user of the model.
     *
     * @see    getTransformIntoModelData()
     * @return null|callable
     */
    public function getTransformFromModelData()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is received from the repository
     *
     * @see    getTransformIntoModelData()
     * @return null|callable
     */
    public function getTransformFromRepository()
    {
        return null;
    }

    /**
     * Optionally returns a Closure that can transform model data as it is sent to the repository
     *
     * @see    getTransformIntoModelData()
     * @return null|callable
     */
    public function getTransformIntoRepository()
    {
        return null;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
