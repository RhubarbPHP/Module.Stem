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

namespace Rhubarb\Stem;

use Rhubarb\Crown\Settings;

/**
 * Common settings needed for modelling.
 *
 */
class StemSettings extends Settings
{
    public $host = "";
    public $port = 3306;
    public $username = "";
    public $password = "";
    public $database = "";

    // allows connections to be split so that reads and writes
    public $readOnlyHost = "";
    public $readOnlyPort = "";
    public $readOnlyUsername = "";
    public $readOnlyPassword = "";

    // Useful if you intend to read immediately after writing and your read only connection has lag (eg replication).
    // This will probably make read only connections pointless if writes happen throughout reads (eg db logging).
    public $stickyWriteConnection = true;

    /**
     * @var \DateTimeZone
     */
    public $projectTimeZone;

    /**
     * @var \DateTimeZone
     */
    public $repositoryTimeZone;

    /** @var string Will be used by the PDO client */
    public $charset;

    protected function initialiseDefaultValues()
    {
        parent::initialiseDefaultValues();

        $this->projectTimeZone = new \DateTimeZone(date_default_timezone_get());
    }

    public $pdoOptions = [];
}
