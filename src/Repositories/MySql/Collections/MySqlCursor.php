<?php

namespace Rhubarb\Stem\Repositories\MySql\Collections;

use Rhubarb\Stem\Collections\CollectionCursor;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\ModelSchema;

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

    private $rowsFetched = [];

    private $rowCount = 0;

    public function __construct(\PDOStatement $statement, Repository $repository)
    {
        $this->statement = $statement;
        $this->repository = $repository;
        $this->schema = $repository->getModelSchema();
        $this->modelClassName = $repository->getModelClass();
        $this->uniqueIdentifier = $this->schema->uniqueIdentifierColumnName;
        $this->rowCount = $this->statement->rowCount();
    }

    public function filterModelsByIdentifier($uniqueIdentifiers)
    {

    }

    public function offsetGet($index)
    {
        while($this->lastFetchedRow < $index){
            $row = $this->statement->fetch(\PDO::FETCH_ASSOC);

            $this->lastFetchedRow++;

            if ($row){
                $this->currentRow = $row;
                $this->repository->cachedObjectData[$row[$this->uniqueIdentifier]] = $row;
                $this->rowsFetched[$this->lastFetchedRow] = $row[$this->uniqueIdentifier];
            }
        }

        $id = $this->rowsFetched[$index];

        $class = $this->modelClassName;

        /**
         * @var Model $object
         */
        $object = new $class($id);

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
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->index;
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
        return ($this->index < $this->count());
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->index = -1;
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
        return ($offset < $this->count() && $offset >= 0);
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
        return $this->rowCount;
    }
}