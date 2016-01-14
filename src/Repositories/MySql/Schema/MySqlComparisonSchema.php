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

use Rhubarb\Stem\Repositories\Repository;

class MySqlComparisonSchema
{
    public $columns = [];
    public $indexes = [];

    public function createAlterTableStatementFor(MySqlComparisonSchema $targetSchema)
    {
        $statements = [];

        foreach ($this->columns as $columnName => $definition) {
            if (!isset($targetSchema->columns[$columnName])) {
                $statements[] = "ADD COLUMN " . $definition;
            } else {
                if (strtolower($targetSchema->columns[$columnName]) != strtolower($definition)) {
                    $statements[] = "CHANGE COLUMN `$columnName` " . $definition;
                }
            }
        }

        foreach ($this->indexes as $index) {
            if (!in_array($index, $targetSchema->indexes)) {
                $statements[] = "ADD " . $index;
            }
        }

        if (sizeof($statements) == 0) {
            return false;
        }

        return implode(",\r\n", $statements);
    }

    public static function fromMySqlSchema(MySqlModelSchema $schema)
    {
        $comparisonSchema = new MySqlComparisonSchema();
        $columns = $schema->getColumns();

        foreach ($columns as $column) {
            // The column might be using more generic types for it's storage.
            $storageColumns = $column->createStorageColumns();
            foreach ($storageColumns as $storageColumn) {
                // And if so that column will be a generic column type - we need to upgrade it.
                $storageColumn = $storageColumn->getRepositorySpecificColumn("MySql");
                $comparisonSchema->columns[$storageColumn->columnName] = trim($storageColumn->getDefinition());
            }
        }

        $indexes = $schema->getIndexes();

        foreach ($indexes as $index) {
            $comparisonSchema->indexes[] = $index->getDefinition();
        }

        return $comparisonSchema;
    }

    /**
     * Returns a MySqlComparisonSchema reflecting the schema of a database table.
     *
     * @param  $tableName
     * @return MySqlComparisonSchema
     */
    public static function fromTable($tableName)
    {
        $repos = Repository::getDefaultRepositoryClassName();

        // Get the create table syntax for the table - we'll analyse this and build our schema accordingly.
        $row = $repos::returnFirstRow(
            "SHOW CREATE TABLE $tableName"
        );

        $sql = $row["Create Table"];

        $lines = explode("\n", $sql);

        // First and last lines aren't needed
        $lines = array_slice($lines, 1, -1);

        $comparisonSchema = new MySqlComparisonSchema();

        foreach ($lines as $line) {
            $line = trim($line);

            // If the line starts with a back tick we have a column
            if ($line[0] == "`") {
                $default = null;

                preg_match("/`([^`]+)`/", rtrim($line, ','), $matches);

                $name = $matches[1];

                $comparisonSchema->columns[$name] = rtrim(trim($line), ",");
            } else {
                $words = explode(" ", $line);

                $indexKeywords = ["PRIMARY", "KEY", "UNIQUE", "FULLTEXT"];

                if (in_array($words[0], $indexKeywords)) {
                    $index = preg_replace('/\s+/', ' ', rtrim(trim($line), ","));
                    $index = preg_replace('/^UNIQUE KEY/', 'UNIQUE', $index);
                    $comparisonSchema->indexes[] = $index;
                }
            }
        }

        return $comparisonSchema;
    }
}
