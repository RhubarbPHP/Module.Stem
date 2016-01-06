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

/**
 * Schema details for an index
 */
class Index
{
    const INDEX = 0;
    const PRIMARY = 1;
    const UNIQUE = 2;
    const FULLTEXT = 3;

    /**
     * The name of the index
     *
     * @var
     */
    public $indexName;

    /**
     * The type of index
     *
     * One of Index::Index, Index::Primary, Index::Unique
     *
     * @var
     */
    public $indexType = Index::INDEX;

    /**
     * A collection of column names included in the index.
     *
     * @var array
     */
    public $columnNames = [];

    /**
     * Creates an index.
     *
     * @param string $indexName   If the type is INDEX::PRIMARY then this will be force to PRIMARY
     * @param int    $indexType   One of Index::INDEX, Index::PRIMARY, Index::UNIQUE or Index::FULLTEXT
     * @param null   $columnNames If null, then an array with just the index name is assumed.
     */
    public function __construct($indexName, $indexType, $columnNames = null)
    {
        $this->indexType = $indexType;

        if ($columnNames === null) {
            $columnNames = [$indexName];
        }

        if (!is_array($columnNames)) {
            $columnNames = [$columnNames];
        }

        $this->columnNames = $columnNames;

        if ($this->indexType == Index::PRIMARY) {
            $indexName = "Primary";
        }

        $this->indexName = $indexName;
    }

    /**
     * Returns the definition for this index.
     * @return string
     */
    public function getDefinition()
    {
        $columnNames = " (`" . implode("`,`", $this->columnNames) . "`)";
        switch ($this->indexType) {
        case Index::PRIMARY:
            return "PRIMARY KEY" . $columnNames;
                break;
        case Index::INDEX:
            return "KEY `" . $this->indexName . "`" . $columnNames;
                break;
        case Index::UNIQUE:
            return "UNIQUE `" . $this->indexName . "`" . $columnNames;
                break;
        case Index::FULLTEXT:
            return "FULLTEXT KEY `" . $this->indexName . "`" . $columnNames;
                break;
        }

        return "";
    }
}
