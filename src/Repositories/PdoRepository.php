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

namespace Rhubarb\Stem\Repositories;

require_once __DIR__ . '/Repository.php';

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\StemSettings;

abstract class PdoRepository extends Repository
{
    protected static $lastStatement = "";

    protected static $lastParams = [];

    protected static $secondLastStatement = "";

    protected $lastSortsUsed = [];

    /**
     * The default connection to use if an explicit connection isn't passed in.
     *
     * @var /PDO
     */
    protected static $defaultConnection = null;

    protected static $readOnlyConnection = null;

    private static $pdoParamAliasesUsed = [];

    /**
     * Resets the param aliases used in PDO - used when unit testing to verify query outputs consistantly.
     */
    public static function resetPdoParamAliases()
    {
        self::$pdoParamAliasesUsed = [];
    }

    /**
     * Returns a guaranteed unique parameter name for the column
     * @param $columnName
     * @return string
     */
    public static function getPdoParamName($columnName)
    {
        if (isset(self::$pdoParamAliasesUsed[$columnName])) {
            self::$pdoParamAliasesUsed[$columnName]++;

            return $columnName . self::$pdoParamAliasesUsed[$columnName];
        } else {
            self::$pdoParamAliasesUsed[$columnName] = 1;

            return $columnName;
        }
    }

    /**
     * Return's the default connection.
     */
    public static function getDefaultConnection()
    {
        if (self::$defaultConnection === null) {
            $databaseSettings = StemSettings::singleton();

            self::$defaultConnection = static::getConnection($databaseSettings);

            if ($databaseSettings->stickyWriteConnection) {
                self::$readOnlyConnection = self::$defaultConnection;
            }
        }

        return self::$defaultConnection;
    }

    public static function getReadOnlyConnection()
    {
        if (self::$readOnlyConnection === null) {
            $databaseSettings = StemSettings::singleton();

            if (
                // readonly port/host are different to default
                (($databaseSettings->readOnlyHost !== '') && ($databaseSettings->readOnlyHost !== $databaseSettings->host))
                || (($databaseSettings->readOnlyPort !== '') && ($databaseSettings->readOnlyPort !== $databaseSettings->port))
            ) {
                $readOnlySettings = clone StemSettings::singleton();
                $readOnlyMap = [
                    'host' => 'readOnlyHost',
                    'port' => 'readOnlyPort',
                    'username' => 'readOnlyUsername',
                    'password' => 'readOnlyPassword',
                ];
                foreach ($readOnlyMap as $primaryProp => $readOnlyProp) {
                    if ($readOnlySettings->$readOnlyProp) {
                        $readOnlySettings->$primaryProp = $readOnlySettings->$readOnlyProp;
                    }
                }
                self::$readOnlyConnection = static::getConnection($readOnlySettings);
            } else {
                self::$readOnlyConnection = static::getDefaultConnection();
            }
        }

        return self::$readOnlyConnection;
    }

    /**
     * @param StemSettings $dbSettings
     * @return \PDO
     * @throws \Rhubarb\Stem\Exceptions\RepositoryConnectionException
     */
    public static function getConnection(StemSettings $dbSettings)
    {
        throw new RepositoryConnectionException("This repository has no getConnection() implementation.");
    }

    /**
     * Discards the default connection.
     */
    public static function resetDefaultConnection()
    {
        self::$defaultConnection = null;
    }

    /**
     * Discards the default connection.
     */
    public static function resetReadOnlyConnection()
    {
        self::$readOnlyConnection = null;
    }

    /**
     * A collection of PDO objects for each active connection.
     *
     * @var /PDO[]
     */
    protected static $connections = [];

    /**
     * Returns the last SQL statement executed.
     *
     * Used by unit tests to ensure performance optimisations have taken effect.
     */
    public static function getPreviousStatement($secondLast = false)
    {
        return ($secondLast) ? self::$secondLastStatement : self::$lastStatement;
    }

    /**
     * Returns the last SQL parameters used.
     *
     * Used by unit tests to ensure interactions with the database are correct.
     */
    public static function getPreviousParameters()
    {
        return self::$lastParams;
    }

    public function canFilterExclusivelyByRepository(RepositoryCollection $collection, &$namedParams = [], &$propertiesToAutoHydrate = [])
    {
        $filteredExclusivelyByRepository = true;

        $filter = $collection->getFilter();

        if ($filter !== null) {
            $filter->filterWithRepository($this, $namedParams, $propertiesToAutoHydrate);

            $filteredExclusivelyByRepository = $filter->wasFilteredByRepository();
        }

        return $filteredExclusivelyByRepository;
    }

    public function reHydrateObject(Model $object, $uniqueIdentifier)
    {
        unset($this->cachedObjectData[$uniqueIdentifier]);

        $this->hydrateObject($object, $uniqueIdentifier);
    }

    protected function getManualSortsRequiredForList(RepositoryCollection $list)
    {
        $sorts = $list->getSorts();

        $sorts = array_diff_key($sorts, array_flip($this->lastSortsUsed));

        return $sorts;
    }

    /**
     * Executes the statement with any supplied named parameters on the connection provided.
     *
     * If no connection is provided the default connection will be used.
     *
     * @param       $statement
     * @param array $namedParameters
     * @param \PDO $connection
     * @param null $insertedId Will contain the ID of the last inserted record after statement is executed
     *
     * @return \PDOStatement
     * @throws RepositoryStatementException
     */
    public static function executeStatement($statement, $namedParameters = [], $connection = null, &$insertedId = null)
    {
        if ($connection === null) {
            $connection = static::getDefaultConnection();
        }

        self::$secondLastStatement = self::$lastStatement;
        self::$lastStatement = $statement;
        self::$lastParams = $namedParameters;

        $pdoStatement = $connection->prepare($statement);

        Log::createEntry(Log::PERFORMANCE_LEVEL | Log::REPOSITORY_LEVEL, function () use ($statement, $namedParameters, $connection) {
            $newStatement = $statement;

            if (is_array($namedParameters)){
                array_walk($namedParameters, function ($value, $key) use (&$newStatement, &$params, $connection) {
                    // Note this is not attempting to make secure queries - this is purely illustrative for the logs
                    // However we do at least do addslashes so if you want to cut and paste a query from the log to
                    // try it - it should work in most cases.
                    $newStatement = preg_replace('/(\:' . preg_quote($key) . ')([^\w]|$)/' , $connection->quote($value) . '$2', $newStatement);
                });
            }

            return "Executing PDO statement " . $newStatement;
        }, "PDO");

        try{
            if (!$pdoStatement->execute($namedParameters)) {
                $error = $pdoStatement->errorInfo();

                throw new RepositoryStatementException($error[2], $statement);
            }
        }catch(\PDOException $exception){
            throw new RepositoryStatementException($exception->getMessage(), $statement);
        }

        $insertedId = $connection->lastInsertId();

        Log::createEntry(Log::PERFORMANCE_LEVEL | Log::REPOSITORY_LEVEL, "Statement successful", "PDO");

        return $pdoStatement;
    }

    public static function executeInsertStatement($sql, $namedParameters = [], $connection = null)
    {
        self::executeStatement($sql, $namedParameters, $connection, $insertedId);

        return $insertedId;
    }

    /**
     * Executes the statement and returns the first column of the first row.
     *
     * @param $statement
     * @param array $namedParameters
     * @param null $connection
     * @return string
     */
    public static function returnSingleValue($statement, $namedParameters = [], $connection = null)
    {
        $statement = self::executeStatement(
            $statement,
            $namedParameters,
            $connection !== null ? $connection : static::getReadOnlyConnection()
        );

        return $statement->fetchColumn(0);
    }

    /**
     * Returns the first row of results from the statement
     *
     * @param $statement
     * @param array $namedParameters
     * @param null $connection
     * @return string
     */
    public static function returnFirstRow($statement, $namedParameters = [], $connection = null)
    {
        $statement = self::executeStatement(
            $statement,
            $namedParameters,
            $connection !== null ? $connection : static::getReadOnlyConnection()
        );

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
}
