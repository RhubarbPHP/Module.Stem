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

namespace Rhubarb\Stem\Repositories\MySql\Schema;

require_once __DIR__ . "/../../../Schema/ModelSchema.php";

use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * An implementation of Schema for MySQL databases.
 */
class MySqlModelSchema extends ModelSchema
{
    /**
     * A collection of Index objects
     *
     * Don't add directly to this collection, use addIndex() instead.
     *
     * @see MySqlSchema::addIndex()
     * @var Index[]
     */
    public $indexes = array();

    /**
     * Adds an index to the indexes collection.
     *
     * @param Index $index
     */
    public function addIndex(Index $index)
    {
        $this->indexes[$index->indexName] = $index;
    }

    /**
     * Check to see if the back end schema is up to date - if not update it.
     */
    public function checkSchema()
    {
        try {
            $repos = Repository::getDefaultRepositoryClassName();

            if (ltrim($repos, '\\') != 'Rhubarb\Stem\Repositories\MySql\MySql') {
                // If our repos has been switched to something that isn't MySql (e.g. Offline if unit testing)
                // we need to exit.

                return;
            }

            $existingSchema = MySqlComparisonSchema::fromTable($this->schemaName);
            $testSchema = MySqlComparisonSchema::fromMySqlSchema($this);

            $alterStatement = $testSchema->createAlterTableStatementFor($existingSchema);

            if ($alterStatement != false) {
                $alterStatement = "ALTER TABLE " . $this->schemaName . "\r\n" . $alterStatement;

                try {
                    $repos::executeStatement($alterStatement);
                } catch (RepositoryStatementException $er) {
                    // The update of the schema failed - probably meaning bad news!
                    /// TODO: Handle reporting of this information.
                }
            }
        } catch (RepositoryStatementException $er) {
            $this->createTable();
        }
    }

    public function addColumn(Column $column)
    {
        $columns = func_get_args();

        call_user_func_array("parent::addColumn", $columns);

        foreach ($columns as $column) {

            if (method_exists($column, "getIndex")) {
                $index = $column->getIndex();

                if ($index !== false) {
                    $this->addIndex($index);
                }
            }
        }
    }

    /**
     * Creates the table in the back end data store.
     */
    private function createTable()
    {
        $sql = "CREATE TABLE " . $this->schemaName . " (";

        $definitions = array();

        foreach ($this->columns as $columnName => $column) {
            $definitions[] = $column->getDefinition();
        }

        foreach ($this->indexes as $indexName => $index) {
            $definitions[] = $index->getDefinition();
        }

        $sql .= implode(",", $definitions);
        $sql .= "
			)";

        $repos = Repository::getDefaultRepositoryClassName();
        $repos::executeStatement($sql);
    }

    public static function fromGenericSchema(ModelSchema $genericSchema, Repository $repository)
    {
        $schema = new MySqlModelSchema($genericSchema->schemaName);

        $columns = $genericSchema->columns;

        $specificColumns = $genericSchema->getColumnsAsRepositorySpecificTypes($repository);

        call_user_func_array([$schema, "addColumn"], $specificColumns);

        $schema->uniqueIdentifierColumnName = $genericSchema->uniqueIdentifierColumnName;

        return $schema;
    }
}
