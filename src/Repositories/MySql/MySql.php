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

use PDO;
use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Collections\CollectionJoin;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Collections\MySqlCursor;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Sql\ExpressesSqlInterface;
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

    public function getRepositorySpecificFilter(Filter $filter)
    {
        // Get the provider specific implementation of the filter.
        $className = __NAMESPACE__ . "\\Filters\\MySql" . basename(str_replace("\\", "/", get_class($filter)));

        if (class_exists($className)) {
            return $className::fromGenericFilter($filter);
        }

        return false;
    }

    public function getRepositorySpecificAggregate(Aggregate $aggregate)
    {
        // Get the provider specific implementation of the filter.
        $className = __NAMESPACE__ . "\\Aggregates\\MySql" . basename(str_replace("\\", "/", get_class($aggregate)));

        if (class_exists($className)) {
            return $className::fromGenericAggregate($aggregate);
        }

        return false;
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
            ["id" => $uniqueIdentifier],
            static::getReadOnlyConnection()
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
     * @return int|void
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

        $filter = $collection->getFilter();

        if ($filter && !$collection->getFilter()->canFilterWithRepository($collection, $this)){
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
     * Provides an opportunity for a collection's size to be calculated in an optimised way
     * 
     * By default just gets a collection cursor and counts the rows.
     */
    public function countRowsInCollection(RepositoryCollection $collection)
    {        
        $clone = clone $collection;
        $clone->disableRanging();

        $sqlStatement = $this->getSqlStatementForCollection($clone, $params);        
        
        if ($sqlStatement->columns[0] instanceof SelectExpression){
            if (stripos($sqlStatement->columns[0]->expression, "*") !== false) {
                // The query includes a wild card select on the outermost table. In some instances there can
                // be a pull up that clashes with column names in the outermost table. This is okay for a normal
                // result set (right most column wins) but when you wrap with a SELECT COUNT(*) MySql complains about
                // the duplicate column. Easiest solution is to change the * for just the ID.
                //
                // Note that while we don't care about the data in all pull up columns we can't remove them as sometimes
                // orders and having clauses expect them to be in the select list.
                //
                // Note also that we alias the ID as uniqid() as sometimes the pull ups are pulling up an identifier column
                // from a joined table and the names can still clash.
                $sqlStatement->columns[0]->expression = str_replace("*", $collection->getModelSchema()->uniqueIdentifierColumnName." AS ".uniqid(), $sqlStatement->columns[0]->expression);                                
            }
        }

        $sqlStatement = "SELECT COUNT(*) AS rowCount FROM (".$sqlStatement.") AS countableList";
        $connection = static::getReadOnlyConnection();        
        $statement = static::executeStatement((string)$sqlStatement, $params, $connection);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row["rowCount"];
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

        $sqlStatement = $this->getSqlStatementForCollection($collection, $params);
        $hasLimit = $sqlStatement->hasLimit();

        $sql = (string)$sqlStatement;

        if ($hasLimit){
            $sql = preg_replace("/^SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql);
        }

        $connection = static::getReadOnlyConnection();
        $statement = static::executeStatement((string)$sql, $params, $connection);

        $count = $statement->rowCount();

        if ($hasLimit){
            $count = static::returnSingleValue("SELECT FOUND_ROWS()", [], $connection);
        }

        $cursor = new MySqlCursor($statement, $this, $count, $collection->additionalColumns);
        // If the number of groups in the statement match those demanded by the collection we can then
        // assume that all grouping has executed in the repository. If not then we set the flag accordingly
        // so that the Collection class can manually group the rows later.
        $cursor->grouped = count($collection->getGroups()) == count($sqlStatement->groups);


        $filter = $collection->getFilter();

        if ($filter){
            $cursor->filtered = $filter->wasFilteredByRepository();
        }

        $cursor->setHydrationMappings($sqlStatement->potentialHydrationMappings);

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
        return $this->getSqlStatementForCollectionWithColumns($collection, $namedParams);
    }

    /**
     * @param RepositoryCollection $collection
     * @param string[]             $namedParams
     * @param array                $columns The columns to populate in the collection. Empty results in all columns.
     *
     * @return SqlStatement
     */
    public function getSqlStatementForCollectionWithColumns(RepositoryCollection $collection, &$namedParams, array $columns = [])
    {
        $model = $collection->getModelClassName();
        $schema = SolutionSchema::getModelSchema($model);
        $sqlStatement = new SqlStatement();
        $sqlStatement->setAlias($collection->getUniqueReference());
        $sqlStatement->schemaName = $schema->schemaName;
        if (count($columns) > 0) {
            foreach ($columns as $column) {
                $sqlStatement->columns[] = new SelectExpression("`" . $sqlStatement->getAlias() . "`.{$column}");
            }
            $columns = array_intersect_key($schema->getColumns(), array_flip($columns));
        } else {
            $sqlStatement->columns[] = new SelectExpression("`" . $sqlStatement->getAlias() . "`.*");
            $columns = $schema->getColumns();
        }

        $allIntersected = true;

        $intersectionColumnAliases = [];

        foreach($columns as $columnName => $column){
            $intersectionColumnAliases[$columnName] = $sqlStatement->getAlias();
        }

        $hydrationMappings = [];

        foreach($collection->getIntersections() as $collectionJoin){

            if (!($collectionJoin->collection instanceof RepositoryCollection)){
                $allIntersected = false;
                continue;
            }

            if (!$collectionJoin->collection->canBeFilteredByRepository()){
                $allIntersected = false;
                continue;
            }

            $join = new Join();

            $intersectionRepository = $collectionJoin->collection->getRepository();

            $join->statement = $intersectionRepository->getSqlStatementForCollection($collectionJoin->collection, $namedParams, $collectionJoin->targetColumnName);
            if($collectionJoin->joinType == CollectionJoin::JOIN_TYPE_INTERSECTION)
            {
                $join->joinType = Join::JOIN_TYPE_INNER;
            }
            else if($collectionJoin->joinType == CollectionJoin::JOIN_TYPE_ATTACH)
            {
                $join->joinType = Join::JOIN_TYPE_LEFT;
            }

            $join->parentTableAlias = !isset($columns[$collectionJoin->sourceColumnName]) && isset($intersectionColumnAliases[$collectionJoin->sourceColumnName]) ?
                $intersectionColumnAliases[$collectionJoin->sourceColumnName] :
                $sqlStatement->getAlias();

            $join->parentColumn = $collectionJoin->sourceColumnName;
            $join->childColumn = $collectionJoin->targetColumnName;

            $sqlStatement->joins[] = $join;

            $intersectionCollectionColumns = [];
            $intersectionCollectionSchemaColumns = $collectionJoin->collection->getModelSchema()->getColumns();

            foreach($intersectionCollectionSchemaColumns as $column){
                $intersectionCollectionColumns = array_merge($intersectionCollectionColumns,$column->getStorageColumns());
            }

            foreach($intersectionCollectionColumns as $columnName => $column){
                if (!isset($intersectionColumnAliases[$columnName])){
                    $intersectionColumnAliases[$columnName] = $join->statement->getAlias();
                }
            }

            foreach($collectionJoin->columnsToPullUp as $column => $alias){
                if (is_numeric($column)){
                    $column = $alias;
                }

                // To pull this column up directly in the SQL we need to first make sure the column is in the select
                // list of our query or a native column to the schema. Some aggregates may not be computable in SQL
                // so may not be in the query yet.

                $inQuery = false;

                // Native columns
                if (isset($intersectionCollectionColumns[$column])){
                    $inQuery = true;
                    $columnDef = clone $intersectionCollectionColumns[$column];
                    $columnDef->columnName = $alias;
                    $collection->additionalColumns[$alias] = ["column" => $columnDef->getRepositorySpecificColumn(self::class), "collection" => $collectionJoin->collection];
                }

                // Nested pull ups of native columns
                if (isset($collectionJoin->collection->additionalColumns[$column])){
                    $inQuery = true;
                    $columnDef = clone $collectionJoin->collection->additionalColumns[$column]["column"];
                    $columnDef->columnName = $alias;
                    $collection->additionalColumns[$alias] = ["column" => $columnDef->getRepositorySpecificColumn(self::class), "collection" => $collectionJoin->collection];
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

                    // We found it, we should make sure there's a mapping for this column so that it can be referenced later
                    if (!isset($intersectionColumnAliases[$column])) {
                        $intersectionColumnAliases[$column] = $join->statement->getAlias();
                    }
                }
            }

            // If we need to auto hydrate, select the columns in the outer query.
            if ($collectionJoin->autoHydrate){
                $intersectionColumns = $intersectionRepository->getModelSchema()->getColumns();
                $primaryKey = $intersectionRepository->getModelSchema()->uniqueIdentifierColumnName;
                foreach($intersectionColumns as $hydrateColumn){

                    $storageColumns = $hydrateColumn->getStorageColumns();

                    foreach($storageColumns as $storageColumn) {

                        $sqlStatement->columns[] = new SelectExpression("`" . $join->statement->getAlias() . "`.`" . $storageColumn->columnName . "` AS `" . $join->statement->getAlias() . $storageColumn->columnName . "`");
                        $hydrationMappings[$join->statement->getAlias() . $storageColumn->columnName] =
                            [
                                $storageColumn->columnName,
                                $primaryKey,
                                $intersectionRepository
                            ];
                    }
                }
            }

            $collectionJoin->intersected = true;
        }

        $sqlStatement->potentialHydrationMappings = $hydrationMappings;
        $filter = $collection->getFilter();

        $allFiltered = true;

        if ($filter){
            $filter->filterWithRepository($collection, $this, $sqlStatement, $namedParams);
            $allFiltered = $filter->wasFilteredByRepository();
        }

        foreach ($collection->getAggregateColumns() as $aggregate) {
            if (isset($intersectionColumnAliases[$aggregate->getAggregateColumnName()])) {
                $intersectionColumnAliases[$aggregate->getAlias()] = '';
            }
        }

        $sorts = $collection->getSorts();

        $allSorted = true;

        foreach($sorts as $sort){

            $alias = false;

            if (!($sort instanceof ExpressesSqlInterface)) {
                // Custom sorts that express SQL must be accepted

                if (!isset($intersectionColumnAliases[$sort->columnName])) {
                    // Is this an alias?
                    foreach ($sqlStatement->columns as $expression) {
                        if ($expression instanceof SelectColumn) {
                            if ($expression->alias == $sort->columnName) {
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
            }

            $sort->sorted = true;

            if ($sort instanceof ExpressesSqlInterface){
                $sqlStatement->sorts[] = new SortExpression($sort->getSqlExpression());
            } else {
                if ($alias) {
                    $sqlStatement->sorts[] = new SortExpression("`" . $sort->columnName . '`', $sort->ascending);
                } else {
                    $alias = $intersectionColumnAliases[$sort->columnName];

                    if ($alias) {
                        $sqlStatement->sorts[] = new SortExpression("`" . $alias . "`.`" . $sort->columnName . '`', $sort->ascending);
                    } else {
                        $sqlStatement->sorts[] = new SortExpression("`" . $sort->columnName . '`', $sort->ascending);
                    }
                }
            }
        }

        $aggregates = $collection->getAggregateColumns();
        $allAggregated = true;

        // If any of the aggregates can't happen in the repos, then NONE of them can, as the group by won't be
        // added to the query and we'll be iterating through all the rows anyway for the sake of the one aggregate
        // that can't be done in the query.
        foreach ($aggregates as $aggregate) {
            if (!$aggregate->canAggregateWithRepository($this, $collection)){
                $allAggregated = false;
            }
        }

        if ($allAggregated) {
            foreach ($aggregates as $aggregate) {
                $aggregate->aggregateWithRepository($this, $sqlStatement, $collection, $namedParams);
                if (!$aggregate->wasAggregatedByRepository()) {
                    $allAggregated = false;
                }
            }
        }

        if ($allAggregated) {
            $columnAliases = $collection->getAliasedColumns();

            // If the aggregates didn't work, we can't group yet otherwise the rows will collapse and post query
            // aggregates won't have all the rows to work on.
            foreach ($collection->getGroups() as $group) {
                if (isset($columnAliases[$group])){
                    $group = $columnAliases[$group];
                }

                // Check if the column name is from an intersection and update the table alias accordingly.
                $tableAlias = !isset($columns[$group]) && isset($intersectionColumnAliases[$group]) ?
                    $intersectionColumnAliases[$group] :
                    $sqlStatement->getAlias();

                $sqlStatement->groups[] = new GroupExpression("`" . $tableAlias . "`.`" . $group . "`");
            }
        }

        // Only if the repository was able to compute the whole collection in the back end should it
        // put on a limit clause. Otherwise limiting is premature as the full set of rows hasn't been
        // calculated yet.
        if ($allAggregated && $allFiltered && $allSorted && $allIntersected && $collection->isRangingEnabled()) {
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

            $clause = $aggregate->aggregateWithRepository($this, $relationships, $collection);

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
                    array_merge(
                        [\PDO::ERRMODE_EXCEPTION => true, \PDO::MYSQL_ATTR_FOUND_ROWS => true],
                        $settings->pdoOptions
                    )
                );

                $timeZone = $pdo->query("SELECT @@time_zone as tz, @@system_time_zone as stz");
                if ($timeZone->rowCount()) {
                    $timezones = $timeZone->fetch(\PDO::FETCH_ASSOC);
                    if($timezones['tz'] === 'SYSTEM'){
                        $settings->repositoryTimeZone = new \DateTimeZone($timezones['stz']);
                    } else {
                        $settings->repositoryTimeZone = new \DateTimeZone($timezones['tz']);
                    }
                }

                if ($settings->charset) {
                    $statement = $pdo->prepare("SET NAMES :charset");
                    $statement->execute(['charset' => $settings->charset]);
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
