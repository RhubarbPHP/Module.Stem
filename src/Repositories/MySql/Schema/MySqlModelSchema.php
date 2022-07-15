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

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Index;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * An implementation of Schema for MySQL databases.
 */
class MySqlModelSchema extends ModelSchema
{
    /**
     * Check to see if the back end schema is up to date - if not update it.
     *
     * @param Repository $inRepository The repository in which to check the schema
     */
    public function checkSchema(Repository $inRepository)
    {
        try {
            /** @var MySql $repos */
            $repos = get_class($inRepository);

            if (stripos($repos, "MySql") === false) {
                // If our repos has been switched to something that isn't MySql (e.g. Offline if unit testing)
                // we need to exit.

                return;
            }

            $existingSchema = MySqlComparisonSchema::fromTable($this->schemaName);
            $testSchema = MySqlComparisonSchema::fromMySqlSchema($this);

            $alterStatement = $testSchema->createAlterTableStatementFor($existingSchema);

            if ($alterStatement != false) {
                $alterStatement = "ALTER TABLE `" . $this->schemaName . "` " . $alterStatement;

                try {
                    $repos::executeStatement($alterStatement);
                } catch (RepositoryStatementException $er) {
                    // The update of the schema failed - probably meaning bad news!
                    Log::error("Database schema update failed: $this->schemaName", "ERROR", [
                        "SQL Statement" => $alterStatement,
                        "Exception"     => $er->getMessage()
                    ]);
                }
            }
        } catch (RepositoryStatementException $er) {
            $this->createTable();
        }
    }

    private function addFromGenericColumn(Column $column)
    {
        /** @var Column[] $columns */
        $columns = func_get_args();
        $specificColumns = [];

        foreach ($columns as $column) {
            $specificColumns[] = $column->getRepositorySpecificColumn("MySql");
        }

        call_user_func_array("parent::addColumn", $specificColumns);

        foreach ($specificColumns as $column) {

            if (method_exists($column, "getIndex")) {
                $index = $column->getIndex();

                if ($index !== false) {
                    $this->addFromGenericIndex($index);
                }
            }
        }
    }

    private function addFromGenericIndex(Index $index)
    {
        /** @var Index[] $indexes */
        $indexes = func_get_args();
        $specificIndexes = [];

        foreach ($indexes as $index) {
            $specificIndexes[] = $index->getRepositorySpecificIndex("MySql");
        }

        call_user_func_array("parent::addIndex", $specificIndexes);
    }

    /**
     * Creates the table in the back end data store.
     */
    private function createTable()
    {
        $sql = "CREATE TABLE `" . $this->schemaName . "` (";

        $definitions = [];

        foreach ($this->columns as $columnName => $column) {
            // The column might be using a more generic type for it's storage.
            $storageColumns = $column->createStorageColumns();

            foreach ($storageColumns as $storageColumn) {
                // And if so that column will be a generic column type - we need to upgrade it.
                $storageColumn = $storageColumn->getRepositorySpecificColumn("MySql");
                $definitions[] = $storageColumn->getDefinition();
            }
        }

        foreach ($this->indexes as $indexName => $index) {
            $indexDefinition = $index->getDefinition();
            if ($indexDefinition) {
                $definitions[] = $indexDefinition;
            }
        }

        $sql .= implode(",", $definitions);
        $sql .= "
			)";

        /** @var MySql $repos */
        $repos = Repository::getDefaultRepositoryClassName();
        $repos::executeStatement($sql);
    }

    public static function fromGenericSchema(ModelSchema $genericSchema)
    {
        $schema = new MySqlModelSchema($genericSchema->schemaName);
        $schema->labelColumnName = $genericSchema->labelColumnName;

        $columns = $genericSchema->columns;

        // By simply adding the columns to the specific repository versioned schema, the columns
        // should be 'upgraded' automatically.
        call_user_func_array([$schema, "addFromGenericColumn"], array_values($columns));

        $schema->uniqueIdentifierColumnName = $genericSchema->uniqueIdentifierColumnName;

        if (count($genericSchema->indexes)) {
            call_user_func_array([$schema, "addFromGenericIndex"], array_values($genericSchema->indexes));
        }

        return $schema;
    }
}
