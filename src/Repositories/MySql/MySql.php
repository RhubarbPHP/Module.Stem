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

namespace Rhubarb\Stem\Repositories\MySql;

require_once __DIR__ . "/../PdoRepository.php";

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemSettings;

class MySql extends PdoRepository
{
    protected function onObjectSaved(Model $object)
    {
        // If this is a new object, we need to insert it.
        if ($object->isNewRecord()) {
            $this->insertObject($object);
        } else {
            $this->updateObject($object);
        }
    }

    protected function onObjectDeleted(Model $object)
    {
        $schema = $object->getSchema();

        self::executeStatement(
            "DELETE FROM `{$schema->schemaName}` WHERE `{$schema->uniqueIdentifierColumnName}` = :primary",
            ["primary" => $object->UniqueIdentifier]
        );
    }

    /**
     * Fetches the data for a given unique identifier.
     *
     * @param Model $object
     * @param mixed $uniqueIdentifier
     * @param array $relationshipsToAutoHydrate An array of relationship names which should be automatically hydrated
     *                                                 (i.e. joined) during the hydration of this object. Not supported by all
     *                                                 Repositories.
     *
     * @throws RecordNotFoundException
     * @return array
     */
    protected function fetchMissingObjectData(Model $object, $uniqueIdentifier, $relationshipsToAutoHydrate = [])
    {
        $schema = $this->getRepositorySchema();
        $table = $schema->schemaName;

        $data = self::returnFirstRow(
            "SELECT * FROM `" . $table . "` WHERE `{$schema->uniqueIdentifierColumnName}` = :id",
            ["id" => $uniqueIdentifier]
        );

        if ($data != null) {
            return $this->transformDataFromRepository($data);
        } else {
            throw new RecordNotFoundException(get_class($object), $uniqueIdentifier);
        }
    }

    /**
     * Crafts and executes an SQL statement to update the object in MySQL
     *
     * @param \Rhubarb\Stem\Models\Model $object
     */
    private function updateObject(Model $object)
    {
        $schema = $this->reposSchema;
        $changes = $object->getModelChanges();
        $schemaColumns = $schema->getColumns();

        $params = [];
        $columns = [];

        $sql = "UPDATE `{$schema->schemaName}`";

        foreach ($changes as $columnName => $value) {

            if ($columnName == $schema->uniqueIdentifierColumnName) {
                continue;
            }

            $changeData = $changes;

            if (isset($schemaColumns[$columnName])) {
                $storageColumns = $schemaColumns[$columnName]->getStorageColumns();

                $transforms = $this->columnTransforms[$columnName];

                if ($transforms[1] !== null) {
                    $closure = $transforms[1];

                    $transformedData = $closure($changes);

                    if (is_array($transformedData)) {
                        $changeData = $transformedData;
                    } else {
                        $changeData[$columnName] = $transformedData;
                    }
                }

                foreach ($storageColumns as $storageColumnName => $storageColumn) {
                    $value = (isset($changeData[$storageColumnName])) ? $changeData[$storageColumnName] : null;

                    $columns[] = "`" . $storageColumnName . "` = :" . $storageColumnName;

                    $params[$storageColumnName] = $value;
                }
            }
        }

        if (sizeof($columns) <= 0) {
            return;
        }

        $sql .= " SET " . implode(", ", $columns);
        $sql .= " WHERE `{$schema->uniqueIdentifierColumnName}` = :{$schema->uniqueIdentifierColumnName}";

        $params[$schema->uniqueIdentifierColumnName] = $object->UniqueIdentifier;

        $statement = $this->executeStatement($sql, $params);

        return $statement->rowCount();
    }

    /**
     * Crafts and executes an SQL statement to insert the object into MySQL
     *
     * @param \Rhubarb\Stem\Models\Model $object
     */
    private function insertObject(Model $object)
    {
        $schema = $this->reposSchema;
        $changes = $object->takeChangeSnapshot();

        $params = [];
        $columns = [];

        $sql = "INSERT INTO `{$schema->schemaName}`";

        $schemaColumns = $schema->getColumns();

        foreach ($changes as $columnName => $value) {
            $changeData = $changes;

            if (isset($schemaColumns[$columnName])) {
                $storageColumns = $schemaColumns[$columnName]->getStorageColumns();

                $transforms = $this->columnTransforms[$columnName];

                if ($transforms[1] !== null) {
                    $closure = $transforms[1];

                    $transformedData = $closure($changes);

                    if (is_array($transformedData)) {
                        $changeData = $transformedData;
                    } else {
                        $changeData[$columnName] = $transformedData;
                    }
                }

                foreach ($storageColumns as $storageColumnName => $storageColumn) {
                    $value = (isset($changeData[$storageColumnName])) ? $changeData[$storageColumnName] : null;

                    $columns[] = "`" . $storageColumnName . "` = :" . $storageColumnName;

                    if ($value === null) {
                        $value = $storageColumn->defaultValue;
                    }

                    $params[$storageColumnName] = $value;
                }
            }
        }

        if (sizeof($columns) > 0) {
            $sql .= " SET " . implode(", ", $columns);
        } else {
            $sql .= " VALUES ()";
        }

        $insertId = self::executeInsertStatement($sql, $params);

        if ($insertId > 0) {
            $object[$object->getUniqueIdentifierColumnName()] = $insertId;
        }
    }

    public function getFiltersNamespace()
    {
        return 'Rhubarb\Stem\Repositories\MySql\Filters';
    }

    public function batchCommitUpdatesFromCollection(Collection $collection, $propertyPairs)
    {
        $filter = $collection->getFilter();

        $namedParams = [];
        $propertiesToAutoHydrate = [];
        $whereClause = "";

        $filteredExclusivelyByRepository = true;

        if ($filter !== null) {
            $filterSql = $filter->filterWithRepository($this, $namedParams, $propertiesToAutoHydrate);

            if ($filterSql != "") {
                $whereClause .= " WHERE " . $filterSql;
            }

            $filteredExclusivelyByRepository = $filter->wasFilteredByRepository();
        }

        if (!$filteredExclusivelyByRepository || sizeof($propertiesToAutoHydrate)) {
            throw new BatchUpdateNotPossibleException();
        }

        $schema = $this->reposSchema;
        $table = $schema->schemaName;
        $sets = [];

        foreach ($propertyPairs as $key => $value) {
            $paramName = "Update" . $key;

            $namedParams[$paramName] = $value;
            $sets[] = "`" . $key . "` = :" . $paramName;

        }

        $sql = "UPDATE `{$table}` SET " . implode(",", $sets) . $whereClause;

        MySql::executeStatement($sql, $namedParams);
    }

    /**
     * Gets the unique identifiers required for the matching filters and loads the data into
     * the cache for performance reasons.
     *
     * @param  Collection $list
     * @param  int $unfetchedRowCount
     * @param  array $relationshipNavigationPropertiesToAutoHydrate
     * @return array
     */
    public function getUniqueIdentifiersForDataList(Collection $list, &$unfetchedRowCount = 0, $relationshipNavigationPropertiesToAutoHydrate = [])
    {
        $this->lastSortsUsed = [];

        $schema = $this->reposSchema;

        $sql = $this->getRepositoryFetchCommandForDataList($list, $relationshipNavigationPropertiesToAutoHydrate, $namedParams, $joinColumns, $joinOriginalToAliasLookup, $joinColumnsByModel, $ranged);

        $statement = self::executeStatement($sql, $namedParams);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $uniqueIdentifiers = [];

        if (sizeof($joinColumns)) {
            foreach ($joinColumnsByModel as $joinModel => $modelJoinedColumns) {
                $model = SolutionSchema::getModel($joinModel);
                $repository = $model->getRepository();

                foreach ($results as &$result) {
                    $aliasedUniqueIdentifierColumnName = $joinOriginalToAliasLookup[$joinModel . "." . $model->UniqueIdentifierColumnName];

                    if (isset($result[$aliasedUniqueIdentifierColumnName]) && !isset($repository->cachedObjectData[$result[$aliasedUniqueIdentifierColumnName]])) {
                        $joinedData = array_intersect_key($result, $modelJoinedColumns);

                        $modelData = array_combine($modelJoinedColumns, $joinedData);

                        $repository->cachedObjectData[$modelData[$model->UniqueIdentifierColumnName]] = $modelData;
                    }

                    $result = array_diff_key($result, $modelJoinedColumns);
                }
                unset($result);
            }
        }

        foreach ($results as $result) {
            $uniqueIdentifier = $result[$schema->uniqueIdentifierColumnName];

            $result = $this->transformDataFromRepository($result);

            // Store the data in the cache and add the unique identifier to our list.
            $this->cachedObjectData[$uniqueIdentifier] = $result;

            $uniqueIdentifiers[] = $uniqueIdentifier;
        }

        if ($ranged) {
            $foundRows = Mysql::returnSingleValue("SELECT FOUND_ROWS()");

            $unfetchedRowCount = $foundRows - sizeof($uniqueIdentifiers);
        }

        if ($list->getFilter() && !$list->getFilter()->wasFilteredByRepository()) {
            Log::warning("A query wasn't completely filtered by the repository", "STEM", $sql);
        }

        return $uniqueIdentifiers;
    }

    /**
     * Returns the repository-specific command so it can be used externally for other operations.
     * This method should be used internally by @see GetUniqueIdentifiersForDataList() to avoid duplication of code.
     *
     * @param Collection $collection
     * @param array $relationshipNavigationPropertiesToAutoHydrate An array of property names the caller suggests we
     *                                                                  try to auto hydrate (if supported)
     * @param array $namedParams Named parameters to be used in execution of the command Remaining parameters are passed by reference, only necessary for internal usage by @see GetUniqueIdentifiersForDataList() which requires more than just the SQL command to be returned from this method.
     *
     * Remaining parameters are passed by reference, only necessary for internal usage by @see GetUniqueIdentifiersForDataList() which requires more
     * than just the SQL command to be returned from this method.
     *
     * @param array $joinColumns
     * @param array $joinOriginalToAliasLookup
     * @param array $joinColumnsByModel
     * @param bool $ranged
     *
     * @return string The SQL command to be executed
     */
    public function getRepositoryFetchCommandForDataList(
        Collection $collection,
        $relationshipNavigationPropertiesToAutoHydrate = [],
        &$namedParams = null,
        &$joinColumns = null,
        &$joinOriginalToAliasLookup = null,
        &$joinColumnsByModel = null,
        &$ranged = null
    ) {
        $schema = $this->reposSchema;
        $table = $schema->schemaName;

        $whereClause = "";

        $filter = $collection->getFilter();

        $namedParams = [];
        $propertiesToAutoHydrate = $relationshipNavigationPropertiesToAutoHydrate;

        $filteredExclusivelyByRepository = true;

        if ($filter !== null) {
            $filterSql = $filter->filterWithRepository($this, $namedParams, $propertiesToAutoHydrate);

            if ($filterSql != "") {
                $whereClause .= " WHERE " . $filterSql;
            }

            $filteredExclusivelyByRepository = $filter->wasFilteredByRepository();
        }

        $relationships = SolutionSchema::getAllRelationshipsForModel($this->getModelClass());

        $aggregateColumnClause = "";
        $aggregateColumnClauses = [];
        $aggregateColumnAliases = [];

        $aggregateRelationshipPropertiesToAutoHydrate = [];

        foreach ($collection->getAggregates() as $aggregate) {
            $clause = $aggregate->aggregateWithRepository($this, $aggregateRelationshipPropertiesToAutoHydrate);

            if ($clause != "") {
                $aggregateColumnClauses[] = $clause;
                $aggregateColumnAliases[] = $aggregate->getAlias();
            }
        }

        if (sizeof($aggregateColumnClauses) > 0) {
            $aggregateColumnClause = ", " . implode(", ", $aggregateColumnClauses);
        }

        $aggregateRelationshipPropertiesToAutoHydrate = array_unique($aggregateRelationshipPropertiesToAutoHydrate);

        $joins = [];
        $groups = [];

        foreach ($aggregateRelationshipPropertiesToAutoHydrate as $joinRelationship) {
            /**
             * @var OneToMany $relationship
             */
            $relationship = $relationships[$joinRelationship];

            $targetModelName = $relationship->getTargetModelName();
            $targetModelClass = SolutionSchema::getModelClass($targetModelName);

            /**
             * @var Model $targetModel
             */
            $targetModel = new $targetModelClass();
            $targetSchema = $targetModel->getSchema();

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->reposSchema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
            $groups[] = "`{$table}`.`" . $relationship->getSourceColumnName() . '`';
        }

        $joinColumns = [];
        $joinOriginalToAliasLookup = [];
        $joinColumnsByModel = [];

        $sorts = $collection->getSorts();
        $possibleSorts = [];
        $columns = $schema->getColumns();

        foreach ($sorts as $columnName => $ascending) {
            if (!isset($columns[$columnName])) {
                // If this is a one to one relationship we can still sort by using auto hydration.
                $parts = explode(".", $columnName);
                $relationshipProperty = $parts[0];
                $escapedColumnName = '`' . implode('`.`', $parts) . '`';

                if (isset($relationships[$relationshipProperty]) && ($relationships[$relationshipProperty] instanceof OneToOne)) {
                    $propertiesToAutoHydrate[] = $relationshipProperty;

                    $possibleSorts[] = $escapedColumnName . " " . (($ascending) ? "ASC" : "DESC");
                    $this->lastSortsUsed[] = $columnName;
                } else {
                    // If the request sorts contain any that we can't sort by we must only sort by those
                    // after this column.
                    $possibleSorts = [];
                    $this->lastSortsUsed = [];
                }
            } else {
                $possibleSorts[] = '`' . str_replace('.', '`.`', $columnName) . "` " . (($ascending) ? "ASC" : "DESC");
                $this->lastSortsUsed[] = $columnName;
            }
        }

        $propertiesToAutoHydrate = array_unique($propertiesToAutoHydrate);

        foreach ($propertiesToAutoHydrate as $joinRelationship) {
            /**
             * @var OneToMany $relationship
             */
            $relationship = $relationships[$joinRelationship];

            $targetModelName = $relationship->getTargetModelName();
            $targetModelClass = SolutionSchema::getModelClass($targetModelName);

            /**
             * @var Model $targetModel
             */
            $targetModel = new $targetModelClass();
            $targetSchema = $targetModel->getRepository()->getRepositorySchema();

            $columns = $targetSchema->getColumns();

            foreach ($columns as $column) {
                $storageColumns = $column->getStorageColumns();

                foreach ($storageColumns as $storageColumn) {
                    $columnName = $storageColumn->columnName;

                    $joinColumns[$targetModelName . $columnName] = "`{$joinRelationship}`.`{$columnName}`";
                    $joinOriginalToAliasLookup[$targetModelName . "." . $columnName] = $targetModelName . $columnName;

                    if (!isset($joinColumnsByModel[$targetModelName])) {
                        $joinColumnsByModel[$targetModelName] = [];
                    }

                    $joinColumnsByModel[$targetModelName][$targetModelName . $columnName] = $columnName;
                }
            }

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->reposSchema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
        }

        $joinString = "";
        $joinColumnClause = "";

        if (sizeof($joins)) {
            $joinString = " " . implode(" ", $joins);

            $joinClauses = [];

            foreach ($joinColumns as $aliasName => $columnName) {
                $joinClauses[] = "{$columnName} AS `{$aliasName}`";
            }

            if (sizeof($joinClauses)) {
                $joinColumnClause = ", " . implode(", ", $joinClauses);
            }
        }

        $groupClause = "";

        if (sizeof($groups)) {
            $groupClause = " GROUP BY " . implode(", ", $groups);
        }

        $orderBy = "";
        if (sizeof($possibleSorts)) {
            $orderBy .= " ORDER BY " . implode(", ", $possibleSorts);
        }

        $sql = "SELECT `{$table}`.*{$joinColumnClause}{$aggregateColumnClause} FROM `{$table}`" . $joinString . $whereClause . $groupClause . $orderBy;

        $ranged = false;

        if ($filteredExclusivelyByRepository && (sizeof($possibleSorts) == sizeof($sorts))) {
            $range = $collection->getRange();

            if ($range != false) {
                $ranged = true;
                $sql .= " LIMIT " . $range[0] . ", " . $range[1];
                $sql = preg_replace("/^SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql);
            }
        }

        return $sql;
    }

    /**
     * Computes the given aggregates and returns an array of answers
     *
     * An answer will be null if the repository is unable to answer it.
     *
     * @param \Rhubarb\Stem\Aggregates\Aggregate[] $aggregates
     * @param \Rhubarb\Stem\Collections\Collection $collection
     *
     * @return array
     */
    public function calculateAggregates($aggregates, Collection $collection)
    {
        $propertiesToAutoHydrate = [];
        if (!$this->canFilterExclusivelyByRepository($collection, $namedParams, $propertiesToAutoHydrate)) {
            return null;
        }

        $relationships = SolutionSchema::getAllRelationshipsForModel($this->getModelClass());

        $propertiesToAutoHydrate = array_unique($propertiesToAutoHydrate);
        $joins = [];
        $joinColumns = [];

        foreach ($propertiesToAutoHydrate as $joinRelationship) {
            /**
             * @var OneToMany $relationship
             */
            $relationship = $relationships[$joinRelationship];

            $targetModelName = $relationship->getTargetModelName();
            $targetModelClass = SolutionSchema::getModelClass($targetModelName);

            /**
             * @var Model $targetModel
             */
            $targetModel = new $targetModelClass();
            $targetSchema = $targetModel->getSchema();

            $columns = $targetSchema->getColumns();

            foreach ($columns as $columnName => $column) {
                $joinColumns[$targetModelName . $columnName] = "`{$joinRelationship}`.`{$columnName}`";
                $joinOriginalToAliasLookup[$targetModelName . "." . $columnName] = $targetModelName . $columnName;

                if (!isset($joinColumnsByModel[$targetModelName])) {
                    $joinColumnsByModel[$targetModelName] = [];
                }

                $joinColumnsByModel[$targetModelName][$targetModelName . $columnName] = $columnName;
            }

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->reposSchema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
        }

        $joinString = "";

        if (sizeof($joins)) {
            $joinString = " " . implode(" ", $joins);

            $joinClauses = [];

            foreach ($joinColumns as $aliasName => $columnName) {
                $joinClauses[] = "`" . str_replace('.', '`.`', $columnName) . "` AS `" . $aliasName . "`";
            }
        }

        $clauses = [];
        $clausePositions = [];
        $results = [];

        $index = -1;
        $count = -1;

        $relationships = [];

        foreach ($aggregates as $aggregate) {
            $index++;

            $clause = $aggregate->aggregateWithRepository($this, $relationships);

            if ($clause != "") {
                $count++;
                $clauses[] = $clause;
                $clausePositions[$count] = $index;
            } else {
                $results[$index] = null;
            }
        }

        if (sizeof($clauses)) {
            $schema = $this->getRepositorySchema();
            $namedParams = [];
            $propertiesToAutoHydrate = [];

            $sql = "SELECT " . implode(", ", $clauses) . " FROM `{$schema->schemaName}`" . $joinString;

            $filter = $collection->getFilter();

            if ($filter !== null) {
                $filterSql = $filter->filterWithRepository($this, $namedParams, $propertiesToAutoHydrate);

                if ($filterSql != "") {
                    $sql .= " WHERE " . $filterSql;
                }
            }

            $firstRow = self::returnFirstRow($sql, $namedParams);
            $row = is_array($firstRow) ? array_values($firstRow) : null;

            foreach ($clausePositions as $rowPosition => $resultPosition) {
                $results[$resultPosition] = $row === null ? null : $row[$rowPosition];
            }
        }

        return $results;
    }


    /**
     * Gets a PDO connection.
     *
     * @param    \Rhubarb\Stem\StemSettings $settings
     * @throws   \Rhubarb\Stem\Exceptions\RepositoryConnectionException Thrown if the connection could not be established
     * @internal param $host
     * @internal param $username
     * @internal param $password
     * @internal param $database
     * @return   mixed /PDO
     */
    public static function getConnection(StemSettings $settings)
    {
        $connectionHash = $settings->host . $settings->port . $settings->username . $settings->database;

        if (!isset(PdoRepository::$connections[$connectionHash])) {
            try {
                $pdo = new \PDO(
                    "mysql:host=" . $settings->host . ";port=" . $settings->port . ";dbname=" . $settings->database . ";charset=utf8",
                    $settings->username,
                    $settings->password,
                    [\PDO::ERRMODE_EXCEPTION => true]
                );
            } catch (\PDOException $er) {
                throw new RepositoryConnectionException("MySql", $er);
            }

            PdoRepository::$connections[$connectionHash] = $pdo;
        }

        return PdoRepository::$connections[$connectionHash];
    }

    public static function getManualConnection($host, $username, $password, $port = 3306, $database = null)
    {
        try {
            $connectionString = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8";

            if ($database) {
                $connectionString .= "dbname=" . $database . ";";
            }

            $pdo = new \PDO($connectionString, $username, $password, [\PDO::ERRMODE_EXCEPTION => true]);

            return $pdo;
        } catch (\PDOException $er) {
            throw new RepositoryConnectionException("MySql");
        }
    }

    public function clearRepositoryData()
    {
        $schema = $this->getRepositorySchema();

        self::executeStatement("TRUNCATE TABLE `" . $schema->schemaName . "`");
    }
}
