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

/**
 * Common settings needed for modelling.
 *
 * @property string $Host
 * @property string $Port
 * @property string $Username
 * @property string $Password
 * @property string $Database
 * @property string $Charset
 * @property \DateTimeZone $RepositoryTimeZone
 * @property \DateTimeZone $ProjectTimeZone
 * @property bool $CheckSchemasAutomatically    True to update repository schemas when a request finds the
 *                                               schema version has changed. Turn off where several releases
 *                                               share a cache and database (e.g. blue/green deploys) and run
 *                                               stem:update-schemas as part of the release instead.
 */
class StemSettings extends \Rhubarb\Crown\Settings
{
    protected function initialiseDefaultValues()
    {
        parent::initialiseDefaultValues();

        $this->Port = 3306;
        $this->Charset = 'utf8';
        $this->CheckSchemasAutomatically = true;
        $this->ProjectTimeZone = new \DateTimeZone(date_default_timezone_get());
    }
}
