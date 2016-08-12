<?php

namespace Rhubarb\Stem\Repositories\MySql\Collections;

use Rhubarb\Stem\Collections\CollectionCursor;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * A collection cursor for MySql
 *
 * Supports reading rows individually from the query buffer, aggregates, group by, joins and sorting.
 */
class MySqlCursor extends CollectionCursor
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var ModelSchema
     */
    private $schema;

    /**
     * @var string
     */
    private $modelClassName;

    /**
     * @var string
     */
    private $uniqueIdentifier;

    /**
     * As rows are fetched they are cached here (to support rewind)
     * @var array
     */
    private $rowsFetched = [];

    /**
     * The number of rows selected within the limits of the query
     * @var int
     */
    private $rowCount = 0;

    /**
     * The number of rows selected discounting the limits of the query
     * @var int
     */
    private $totalCount = 0;

    /**
     * The number of rows being filtered by post filtering.
     * @var int
     */
    private $filteredCount = 0;

    /**
     * The IDs of rows to skip because they have been filtered outside of the query.
     * @var array
     */
    private $filteredIds = [];

    /**
     * A mapping of column alias => [ field, primary key, repository ] for hydration of joins to models
     * @var array
     */
    private $hydrationMappings = [];

    public function __construct(\PDOStatement $statement, Repository $repository, $totalCount)
    {
        $this->statement = $statement;
        $this->repository = $repository;
        $this->schema = $repository->getModelSchema();
        $this->modelClassName = $repository->getModelClass();
        $this->uniqueIdentifier = $this->schema->uniqueIdentifierColumnName;
        $this->rowCount = $statement->rowCount();
        $this->totalCount = $totalCount;
    }

    /**
     * Excludes the array of identifiers from the list of selected rows.
     *
     * @param $uniqueIdentifiers
     */
    public function filterModelsByIdentifier($uniqueIdentifiers)
    {
        $this->filteredIds = array_merge($this->filteredIds, $uniqueIdentifiers);
        $this->filteredCount = count($this->filteredIds);
    }

    public function setHydrationMappings($mappings)
    {
        $this->hydrationMappings = $mappings;
    }

    /**
     * Takes a row with hydrated data and extracts it from the array.
     *
     * @param $row
     */
    private function processHydration(&$row)
    {
        if (!count($this->hydrationMappings)){
            return;
        }
        
        $primaryFields = [];
        $rawData = [];

        foreach($row as $key => $value){
            if (isset($this->hydrationMappings[$key])){
                unset($row[$key]);

                $primary = $this->hydrationMappings[$key][0];
                $field = $this->hydrationMappings[$key][1];

                /**
                 * @var Repository $repos
                 */
                $repos = $this->hydrationMappings[$key][2];
                $reposClass = $repos->getModelClass();

                if (!isset($rawData[$reposClass])){
                    $rawData[$reposClass] = [ "repos" => $repos, "data" => []];
                }

                if ($field == $primary){
                    $primaryFields[$reposClass] = $value;
                }

                if (isset($primaryFields[$reposClass])){
                    if (!isset($rawData[$reposClass]["data"][$primaryFields[$reposClass]])){
                        $rawData[$reposClass]["data"][$primaryFields[$reposClass]] = [];
                    }

                    $rawData[$reposClass]["data"][$primaryFields[$reposClass]][$primary] = $value;
                }
            }
        }

        foreach($rawData as $reposClass => $reposDetails){
            $repos = $reposDetails["repos"];
            $data = $reposDetails["data"];
            foreach($data as $id => $rawRow){
                $repos->cachedObjectData[$id] = $repos->transformDataFromRepository($rawRow);
            }
        }
    }

    public function offsetGet($index)
    {
        // Keep selecting rows until we find the row we need. We can't with PDO 'skip' rows unfortunately.
        while($this->lastFetchedRow < $index){
            $row = $this->statement->fetch(\PDO::FETCH_ASSOC);

            $this->lastFetchedRow++;

            if ($row){

                // Strip the row of any hydration columns.
                $this->processHydration($row);

                // Keep a handle on the current row.
                $this->currentRow = $row;

                // Populate the modelling repository data
                $this->repository->cachedObjectData[$row[$this->uniqueIdentifier]] =
                    $this->repository->transformDataFromRepository($row);

                // Remember we've already been here.
                $this->rowsFetched[$this->lastFetchedRow] = $row[$this->uniqueIdentifier];
            }
        }

        $id = $this->rowsFetched[$index];

        if (in_array($id, $this->filteredIds)){
            return $this->offsetGet($index+1);
        }

        $class = $this->modelClassName;

        /**
         * @var Model $object
         */
        $object = new $class($id);

        // If we have data to augment the model data (aggregates etc.) we need to merge it in.
        if (isset($this->augmentationData[$id])){
            $object->mergeRawData($this->augmentationData[$id]);
        }

        return $object;
    }


    private $index = 0;
    private $lastFetchedRow = -1;

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->offsetGet($this->index);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->index++;

        if ($this->valid()) {
            $model = $this->offsetGet($this->index);

            if (in_array($model->UniqueIdentifier, $this->filteredIds)){
                $this->next();
            }
        }
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $model = $this->current();
        return $model->uniqueIdentifier;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->offsetExists($this->index);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return ($offset < ($this->rowCount - $this->filteredCount) && $offset >= 0);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->uniqueIdentifiers[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if (isset($this->uniqueIdentifiers[$offset])) {
            unset($this->uniqueIdentifiers[$offset]);
        }
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->totalCount - $this->filteredCount;
    }
}