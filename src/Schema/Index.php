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

/**
 * Schema details for an index
 */
class Index
{
    const INDEX = 0;

    /**
     * The name of the index
     *
     * @var string
     */
    public $indexName;

    /**
     * The type of index
     *
     * Either Index::Index or a type supported by the specific repository in use
     *
     * @var int
     */
    public $indexType;

    /**
     * A collection of column names included in the index.
     *
     * @var string[]
     */
    public $columnNames;

    /**
     * Creates an index.
     *
     * @param string $indexName Name of the index
     * @param int $indexType Either Index::INDEX or a type supported by the specific repository in use
     * @param null|string[] $columnNames If null, then an array with just the index name is assumed.
     */
    public function __construct($indexName, $indexType = self::INDEX, $columnNames = null)
    {
        $this->indexType = $indexType;

        if ($columnNames === null) {
            $columnNames = [$indexName];
        }

        if (!is_array($columnNames)) {
            $columnNames = [$columnNames];
        }

        $this->columnNames = $columnNames;

        $this->indexName = $indexName;
    }

    /**
     * Returns the definition for this index.
     * @return string
     */
    public function getDefinition()
    {
        return "";
    }

    /**
     * Returns a repository specific version of this index if one is available.
     *
     * If no repository specific version is available $this is passed back.
     *
     * @param string $repositoryClassName
     * @return Index
     */
    final public function getRepositorySpecificIndex($repositoryClassName)
    {
        $reposName = basename(str_replace("\\", "/", $repositoryClassName));

        // Get the provider specific implementation of the column.
        $className = '\Rhubarb\Stem\Repositories\\' . $reposName . '\Schema\\' . $reposName . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            $superType = call_user_func_array($className . "::fromGenericIndexType", [$this]);

            // fromGenericIndexType could return false if it doesn't supply any schema details.
            if ($superType !== false) {
                return $superType;
            }
        }

        return $this;
    }

    /**
     * Returns an instance of the index using a generic index to provide the settings.
     *
     * You should override this if your index is a repository specific implementation.
     *
     * @param  Index $genericIndex
     * @return bool|Index Returns the repository specific index or false if the index doesn't support that.
     */
    protected static function fromGenericIndexType(Index $genericIndex)
    {
        return false;
    }
}
