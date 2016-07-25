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

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Collections\MySqlCursor;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Sql\GroupExpression;
use Rhubarb\Stem\Sql\Join;
use Rhubarb\Stem\Sql\SelectColumn;
use Rhubarb\Stem\Sql\SelectExpression;
use Rhubarb\Stem\Sql\SortExpression;
use Rhubarb\Stem\Sql\SqlStatement;
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

    public function batchCommitUpdatesFromCollection(RepositoryCollection $collection, $propertyPairs)
    {
        $namedParams = [];

        if (!$collection->getFilter()->canFilterWithRepository($collection, $this)){
            throw new BatchUpdateNotPossibleException();
        }

        $statement = $this->getSqlStatementForCollection($collection, $namedParams);

        foreach($collection->getIntersections() as $intersection){
            if (!$intersection->intersected){
                throw new BatchUpdateNotPossibleException();
                break;
            }
        }

        foreach ($propertyPairs as $key => $value) {
            $paramName = "Update" . $key;
            $namedParams[$paramName] = $value;
        }

        $sql = $statement->getUpdateSql(array_keys($propertyPairs));

        static::executeStatement($sql, $namedParams);
    }

    /**
     * Gets the unique identifiers required for the matching filters and loads the data into
     * the cache for performance reasons.
     *
     * @param  RepositoryCollection $list
     * @param  int $unfetchedRowCount
     * @param  array $relationshipNavigationPropertiesToAutoHydrate
     * @return array
     */
    public function getUniqueIdentifiersForDataList(RepositoryCollection $list, &$unfetchedRowCount = 0, $relationshipNavigationPropertiesToAutoHydrate = [])
    {
        $this->lastSortsUsed = [];

        $schema = $this->reposSchema;

        $sql = $this->getSqlStatementForCollection($list, $relationshipNavigationPropertiesToAutoHydrate, $namedParams, $joinColumns, $joinOriginalToAliasLookup, $joinColumnsByModel, $ranged);

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
            $foundRows = static::returnSingleValue("SELECT FOUND_ROWS()");

            $unfetchedRowCount = $foundRows - sizeof($uniqueIdentifiers);
        }

        if ($list->getFilter() && !$list->getFilter()->wasFilteredByRepository()) {
            Log::warning("A query wasn't completely filtered by the repository", "STEM", $sql);
        }

        return $uniqueIdentifiers;
    }

    /**
     * Get's a sorted list of unique identifiers for the supplied list.
     *
     * @param  RepositoryCollection $collection
     * @throws \Rhubarb\Stem\Exceptions\SortNotValidException
     * @return array
     */
    public function createCursorForCollection(RepositoryCollection $collection)
    {
        $params = [];

        $sql = $this->getSqlStatementForCollection($collection, $params);
        $hasLimit = $sql->hasLimit();

        if ($hasLimit){
            $sql = (string) $sql;
            $sql = preg_replace("/^SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql);
        }

        $statement = static::executeStatement((string)$sql, $params);

        $count = $statement->rowCount();

        if ($hasLimit){
            $count = static::returnSingleValue("SELECT FOUND_ROWS()");
        }

        $cursor = new MySqlCursor($statement, $this, $count);
        $cursor->setHydrationMappings($sql->potentialHydrationMappings);
        return $cursor;
    }

    /**
     * Returns the repository-specific command so it can be used externally for other operations.
     * This method should be used internally by @see GetUniqueIdentifiersForDataList() to avoid duplication of code.
     *
     * @param RepositoryCollection $collection
     * @param string[] $namedParams
     * @param string $intersectionColumnName If the collection is an intersection, we pass the column within the collection
     *                                       used for the joins. This is essential to allow aggregate expressions to group
     *                                       correctly.
     * @return SqlStatement The SQL command to be executed
     */
    public function getSqlStatementForCollection(RepositoryCollection $collection, &$namedParams, $intersectionColumnName = "")
    {
        $model = $collection->getModelClassName();
        $schema = SolutionSchema::getModelSchema($model);
        $columns = $schema->getColumns();

        $sqlStatement = new SqlStatement();
        $sqlStatement->setAlias($collection->getUniqueReference());
        $sqlStatement->schemaName = $schema->schemaName;
        $sqlStatement->columns[] = new SelectExpression("`".$sqlStatement->getAlias()."`.*");

        $allIntersected = true;

        $intersectionColumnAliases = [];

        foreach($columns as $columnName => $column){
            $intersectionColumnAliases[$columnName] = $sqlStatement->getAlias();
        }

        $hydrationMappings = [];

        foreach($collection->getIntersections() as $intersection){

            if (!($intersection->collection instanceof RepositoryCollection)){
                $allIntersected = false;
                continue;
            }

            if (!$intersection->collection->canBeFilteredByRepository()){
                $allIntersected = false;
                continue;
            }

            $join = new Join();

            $intersectionRepository = $intersection->collection->getRepository();

            $join->statement = $intersectionRepository->getSqlStatementForCollection($intersection->collection, $namedParams, $intersection->childColumnName);
            $join->joinType = Join::JOIN_TYPE_INNER;
            $join->parentColumn = $intersection->parentColumnName;
            $join->childColumn = $intersection->childColumnName;

            $sqlStatement->joins[] = $join;

            $intersectionCollectionColumns = $intersection->collection->getModelSchema()->getColumns();

            foreach($intersectionCollectionColumns as $columnName => $column){
                $intersectionColumnAliases[$columnName] = $join->statement->getAlias();
            }

            foreach($intersection->columnsToPullUp as $column => $alias){
                if (is_numeric($column)){
                    $column = $alias;
                }

                // To pull this column up directly in the SQL we need to first make sure the column is in the select
                // list of our query or a native column to the schema. Some aggregates may not be computable in SQL
                // so may not be in the query yet.

                $inQuery = false;

                if (isset($intersectionCollectionColumns[$column])){
                    $inQuery = true;
                }

                if (!$inQuery) {

                    foreach ($join->statement->columns as $joinColumn) {
                        if ($joinColumn instanceof SelectExpression) {
                            if (strpos($joinColumn->expression, $alias)) {
                                $inQuery = true;
                            }
                        }
                    }
                }

                if ($inQuery){
                    $sqlStatement->columns[] = new SelectColumn("`" . $join->statement->getAlias() . "`." . $column, $alias);
                }
            }

            // If we need to auto hydrate, select the columns in the outer query.
            if ($intersection->autoHydrate){
                $intersectionColumns = $intersectionRepository->getModelSchema()->getColumns();
                $primaryKey = $intersectionRepository->getModelSchema()->uniqueIdentifierColumnName;
                foreach($intersectionColumns as $hydrateColumn){
                    $sqlStatement->columns[] = new SelectExpression("`".$join->statement->getAlias()."`.`".$hydrateColumn->columnName."` AS `".$join->statement->getAlias().$hydrateColumn->columnName."`");
                    $hydrationMappings[$join->statement->getAlias().$hydrateColumn->columnName] =
                        [
                            $hydrateColumn->columnName,
                            $primaryKey,
                            $intersectionRepository
                        ];
                }
            }

            $intersection->intersected = true;
        }

        $sqlStatement->potentialHydrationMappings = $hydrationMappings;
        $filter = $collection->getFilter();

        $allFiltered = true;

        if ($filter){
            $filter->filterWithRepository($collection, $this, $sqlStatement, $namedParams);
            $allFiltered = $filter->wasFilteredByRepository();
        }

        $sorts = $collection->getSorts();

        $allSorted = true;

        foreach($sorts as $sort){

            $alias = false;

            if (!isset($intersectionColumnAliases[$sort->columnName])) {
                // Is this an alias?
                foreach($sqlStatement->columns as $expression){
                    if ($expression instanceof SelectColumn){
                        if ($expression->alias == $sort->columnName){
                            $alias = true;
                        }
                    }
                }

                if (!$alias) {
                    // We can't sort this column - and therefore we can't sort any secondary sorts either.
                    // We have to leave the remaining sorts to the manual iteration handled by the Collection class.
                    $allSorted = false;
                    break;
                }
            }

            $sort->sorted = true;

            if ($alias){
                $sqlStatement->sorts[] = new SortExpression("`".$sort->columnName.'`', $sort->ascending);
            } else {
                $alias = $intersectionColumnAliases[$sort->columnName];
                $sqlStatement->sorts[] = new SortExpression("`".$alias."`.`".$sort->columnName.'`', $sort->ascending);
            }
        }

        $aggregates = $collection->getAggregateColumns();
        $allAggregated = true;

        // If any of the aggregates can't happen in the repos, then NONE of them can, as the group by won't be
        // added to the query and we'll be iterating through all the rows anyway for the sake of the one aggregate
        // that can't be done in the query.
        foreach ($aggregates as $aggregate) {
            if (!$aggregate->canAggregateWithRepository($this)){
                $allAggregated = false;
            }
        }

        if ($allAggregated) {
            foreach ($aggregates as $aggregate) {
                $aggregate->aggregateWithRepository($this, $sqlStatement, $namedParams);
                if (!$aggregate->wasAggregatedByRepository()) {
                    $allAggregated = false;
                }
            }
        }

        if ($allAggregated) {
            // If the aggregates didn't work, we can't group yet otherwise the rows will collapse and post query
            // aggregates won't have all the rows to work on.
            foreach ($collection->getGroups() as $group) {
                $sqlStatement->groups[] = new GroupExpression("`" . $sqlStatement->getAlias() . "`.`" . $group . "`");
            }
        }

        // Only if the repository was able to compute the whole collection in the back end should it
        // put on a limit clause. Otherwise limiting is premature as the full set of rows hasn't been
        // calculated yet.
        if ($allAggregated && $allFiltered && $allSorted && $allIntersected) {
            $rangeStart = $collection->getRangeStart();
            $rangeEnd = $collection->getRangeEnd();

            if ($rangeStart > 0 || $rangeEnd !== null) {
                $sqlStatement->limit($rangeStart, $rangeEnd - $rangeStart + 1);

                $collection->markRangeApplied();
            }
        }

        return $sqlStatement;
    }

    /**
     * Computes the given aggregates and returns an array of answers
     *
     * An answer will be null if the repository is unable to answer it.
     *
     * @param \Rhubarb\Stem\Aggregates\Aggregate[] $aggregates
     * @param \Rhubarb\Stem\Collections\RepositoryCollection $collection
     *
     * @return array
     */
    public function calculateAggregates($aggregates, RepositoryCollection $collection)
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
     * @param StemSettings $settings
     * @throws RepositoryConnectionException Thrown if the connection could not be established
     * @return \PDO
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
                    [\PDO::ERRMODE_EXCEPTION => true, \PDO::MYSQL_ATTR_FOUND_ROWS => true]
                );

                $timeZone = $pdo->query("SELECT @@system_time_zone");
                if ($timeZone->rowCount()) {
                    $settings->repositoryTimeZone = new \DateTimeZone($timeZone->fetchColumn());
                }
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
