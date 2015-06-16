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

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;

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

        self::executeStatement("DELETE FROM `{$schema->schemaName}` WHERE `{$schema->uniqueIdentifierColumnName}` = :primary",
            ["primary" => $object->UniqueIdentifier]);
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
        $schema = $this->getSchema();
        $table = $schema->schemaName;

        $data = self::returnFirstRow("SELECT * FROM `" . $table . "` WHERE `{$schema->uniqueIdentifierColumnName}` = :id",
            array("id" => $uniqueIdentifier));

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
        $schema = $this->schema;
        $changes = $object->getModelChanges();
        $schemaColumns = $schema->getColumns();

        $params = array();
        $columns = array();

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

                    if ( is_array($transformedData ) ) {
                        $changeData = $transformedData;
                    } else {
                        $changeData[$columnName] = $transformedData;
                    }
                }

                foreach( $storageColumns as $storageColumnName => $storageColumn ) {
                    $value = ( isset($changeData[ $storageColumnName]) ) ? $changeData[ $storageColumnName] : null;

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

        $this->executeStatement($sql, $params);
    }

    /**
     * Crafts and executes an SQL statement to insert the object into MySQL
     *
     * @param \Rhubarb\Stem\Models\Model $object
     */
    private function insertObject(Model $object)
    {
        $schema = $this->schema;
        $changes = $object->takeChangeSnapshot();

        $params = array();
        $columns = array();

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

                    if ( is_array($transformedData ) ) {
                        $changeData = $transformedData;
                    } else {
                        $changeData[$columnName] = $transformedData;
                    }
                }

                foreach( $storageColumns as $storageColumnName => $storageColumn ) {
                    $value = ( isset($changeData[ $storageColumnName]) ) ? $changeData[ $storageColumnName] : null;

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

        $id = self::executeInsertStatement($sql, $params);

        $object[$object->getUniqueIdentifierColumnName()] = $id;
    }

    /**
     * Get's the unique identifiers required for the matching filters and loads the data into
     * the cache for performance reasons.
     *
     * @param Collection $list
     * @param int $unfetchedRowCount
     * @param array $relationshipNavigationPropertiesToAutoHydrate
     * @return array
     */
    public function getUniqueIdentifiersForDataList(
        Collection $list,
        &$unfetchedRowCount = 0,
        $relationshipNavigationPropertiesToAutoHydrate = []
    ) {
        $this->lastSortsUsed = [];

        $schema = $this->schema;
        $table = $schema->schemaName;

        $whereClause = "";

        $filter = $list->getFilter();

        $namedParams = array();
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

        foreach ($list->getAggregates() as $aggregate) {
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

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->schema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
            $groups[] = "`{$table}`.`" . $relationship->getSourceColumnName() . '`';
        }

        $joinColumns = [];
        $joinOriginalToAliasLookup = [];
        $joinColumnsByModel = [];

        $sorts = $list->getSorts();
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

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->schema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
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
            $range = $list->getRange();

            if ($range != false) {
                $ranged = true;
                $sql .= " LIMIT " . $range[0] . ", " . $range[1];
                $sql = preg_replace("/^SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql);
            }
        }

        $statement = self::executeStatement($sql, $namedParams);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $uniqueIdentifiers = array();

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

        return $uniqueIdentifiers;
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

            $joins[] = "LEFT JOIN `{$targetSchema->schemaName}` AS `{$joinRelationship}` ON `{$this->schema->schemaName}`.`" . $relationship->getSourceColumnName() . "` = `{$joinRelationship}`.`" . $relationship->getTargetColumnName() . "`";
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

        $i = -1;
        $c = -1;

        $relationships = [];

        foreach ($aggregates as $aggregate) {
            $i++;

            $clause = $aggregate->aggregateWithRepository($this, $relationships);

            if ($clause != "") {
                $c++;
                $clauses[] = str_replace('.', '`.`', $clause);
                $clausePositions[$c] = $i;
            } else {
                $results[$i] = null;
            }
        }

        if (sizeof($clauses)) {
            $schema = $this->getSchema();
            $namedParams = [];
            $propertiesToAutoHydrate = [];

            $groupClause = "";
            if ($joinString) {
                $groupClause = " GROUP BY `{$schema->schemaName}`.`{$schema->uniqueIdentifierColumnName}`";
            }

            $sql = "SELECT " . implode(", ", $clauses) . " FROM `{$schema->schemaName}`" . $joinString;

            $filter = $collection->getFilter();

            if ($filter !== null) {
                $filterSql = $filter->filterWithRepository($this, $namedParams, $propertiesToAutoHydrate);

                if ($filterSql != "") {
                    $sql .= " WHERE " . $filterSql;
                }
            }

            $sql .= $groupClause;

            $row = array_values(self::returnFirstRow($sql, $namedParams));

            foreach ($clausePositions as $rowPosition => $resultPosition) {
                $results[$resultPosition] = $row[$rowPosition];
            }
        }

        return $results;
    }


    /**
     * Gets a PDO connection.
     *
     * @param \Rhubarb\Stem\StemSettings $settings
     * @throws \Rhubarb\Stem\Exceptions\RepositoryConnectionException Thrown if the connection could not be established
     * @internal param $host
     * @internal param $username
     * @internal param $password
     * @internal param $database
     * @return mixed /PDO
     */
    public static function getConnection(StemSettings $settings)
    {
        $connectionHash = $settings->Host . $settings->Port . $settings->Username . $settings->Database;

        if (!isset(PdoRepository::$connections[$connectionHash])) {
            try {
                $pdo = new \PDO("mysql:host=" . $settings->Host . ";port=" . $settings->Port . ";dbname=" . $settings->Database . ";charset=utf8",
                    $settings->Username, $settings->Password, array(\PDO::ERRMODE_EXCEPTION => true));
            } catch (\PDOException $er) {
                throw new RepositoryConnectionException("MySql");
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

            $pdo = new \PDO($connectionString, $username, $password, array(\PDO::ERRMODE_EXCEPTION => true));

            return $pdo;
        } catch (\PDOException $er) {
            throw new RepositoryConnectionException("MySql");
        }
    }
}