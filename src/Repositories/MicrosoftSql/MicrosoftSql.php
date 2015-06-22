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

namespace Rhubarb\Stem\Repositories\MicrosoftSql;

require_once __DIR__ . '/../PdoRepository.php';

use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\StemSettings;

/**
 * Microsoft SQL Server repository.
 *
 * Note - this only provides support presently for executing direct PDO statements.
 */
class MicrosoftSql extends PdoRepository
{
    public static function getConnection(StemSettings $settings)
    {
        $connectionHash = $settings->Host . $settings->Username . $settings->Database;

        if (!isset(PdoRepository::$connections[$connectionHash])) {
            try {
                $pdo = new \PDO(
                    "sqlsrv:server=" . $settings->Host . ";Database=" . $settings->Database,
                    $settings->Username,
                    $settings->Password,
                    [\PDO::ERRMODE_EXCEPTION => true]);
            } catch (\PDOException $er) {
                throw new RepositoryConnectionException("MicrosoftSql");
            }

            PdoRepository::$connections[$connectionHash] = $pdo;
        }

        return PdoRepository::$connections[$connectionHash];
    }
}