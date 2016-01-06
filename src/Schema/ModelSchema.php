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

namespace Rhubarb\Stem\Schema;

use Rhubarb\Stem\Exceptions\SchemaException;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\Column;

/**
 * A container of schema information for a single record type.
 *
 * This generally represents a database table, but can be used for any purpose.
 *
 * Note that there is no mention of indexes in this class. Indexes, while common,
 * are specific to each data store and not something our core data model needs to
 * concern itself with.
 */
class ModelSchema
{
    /**
     * The name of the schema, e.g. tblCompany
     * @var
     */
    public $schemaName;

    /**
     * A collection of Column objects representing the columns in the schema.
     *
     * Don't add columns directly to this collection - use addColumn() instead.
     *
     * @see Schema::addColumn()
     * @var Columns\Column[]
     */
    protected $columns = [];

    /**
     * The name of the column providing the unique identifier for records in this schema.
     *
     * @var string
     */
    public $uniqueIdentifierColumnName = "";

    /**
     * The name of the column that can be used when a label for the model is required.
     *
     * This can be any of the columns in the schema, or any of the computed properties.
     *
     * @var string
     */
    public $labelColumnName = "";

    public function __construct($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * Adds a column to the column collection.
     *
     * @param Column $column
     * @param Column $column,... any number of columns to add
     */
    public function addColumn(Column $column)
    {
        $columns = func_get_args();

        foreach ($columns as $column) {
            $this->columns[$column->columnName] = $column;

            if ($column instanceof AutoIncrementColumn) {
                $this->uniqueIdentifierColumnName = $column->columnName;
            }
        }
    }

    /**
     * Removes a column from the collection using it's name.
     *
     * Often used when extending a model from a scaffold to make extensive changes.
     *
     * @param $columnName
     */
    public function removeColumnByName($columnName)
    {
        if (isset($this->columns[$columnName])) {
            unset($this->columns[$columnName]);
        }
    }

    /**
     * Returns an array of columns contained in the schema.
     *
     * @return Columns\Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Compare's the schema with the back end data store and makes any necessary modifications.
     *
     * @param Repository $inRepository The repository in which to check the schema
     */
    public function checkSchema( Repository $inRepository )
    {

    }

    /**
     * Destroys the back end data store's version of the schema.
     */
    public function destroySchema()
    {

    }

    public static function fromGenericSchema(ModelSchema $genericSchema)
    {
        throw new SchemaException("The schema class " . get_called_class() . " does not implement fromGenericSchema().");
    }
}