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

namespace Rhubarb\Stem\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Exceptions\SchemaException;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\SolutionSchema;

require_once __DIR__ . "/../../../../Schema/Columns/Column.php";
require_once __DIR__ . "/MySqlColumn.php";

/**
 * Represents a MySql enum column type
 *
 * Note that while MySql supports no explicit default on this column type
 * it will still pick the first column as it's default if it's not nullable.
 *
 * Nullable enums are very dangerous and no explicit default is a bad idea so we
 * force one to be configured.
 */
class MySqlEnum extends Column
{
    use MySqlColumn;

    public $enumValues = [];

    public function __construct($columnName, $defaultValue, $enumValues)
    {
        if ($defaultValue === null || !in_array($defaultValue, $enumValues)) {
            throw new SchemaException("The enum column does not have a default matching one of the enum values.");
        }

        parent::__construct($columnName, $defaultValue);

        $this->enumValues = $enumValues;
    }

    public function getDefinition()
    {
        $enumString = "'" . implode("','", $this->enumValues) . "'";

        $sql = "`" . $this->columnName . "` enum(" . $enumString . ") " . $this->getDefaultDefinition();

        return $sql;
    }

    /**
     * Finds the schema of the specified model and returns the values for the specified enum field
     *
     * @param $modelName
     * @param $enumFieldName
     *
     * @return array
     */
    public static function GetEnumValues($modelName, $enumFieldName)
    {
        $schema = SolutionSchema::getModelSchema($modelName);
        $enum = $schema->getColumns()[$enumFieldName];
        return $enum->enumValues;
    }
}