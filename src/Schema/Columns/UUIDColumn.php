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

use Rhubarb\Stem\Models\Model;

class UUIDColumn extends StringColumn implements ModelValueInitialiserInterface
{
    public function __construct($columnName = 'UUID')
    {
        parent::__construct($columnName, 100, null);
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
        return [new StringColumn($this->columnName, 100, $this->defaultValue)];
    }

    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(
                0,
                0xffff
            ),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(
                0,
                0xffff
            ),
            mt_rand(
                0,
                0xffff
            )
        );
    }

    public function onNewModelInitialising(Model $model)
    {
        $model[$this->columnName] = $this->generateUUID();
    }
}
