<?php

namespace Rhubarb\Stem\Collections;

use Rhubarb\Stem\Models\Model;

/**
 * Creates model objects as required from a list of unique identifiers.
 *
 * For large lists this is much more efficient that creating many hundreds of PHP objects
 * especially if not all the objects will be accessed.
 *
 * This cursor creates model objects as they are needed.
 *
 * @package Rhubarb\Stem\Collections
 */
class ModelListCursor extends CollectionCursor
{
    /**
     * @var Model[]
     */
    private $models = [];

    private $index = -1;

    public function __construct($models)
    {
        $this->models = $models;
    }

    public function filterModelsByIdentifier($uniqueIdentifiersToFilter)
    {
        $filteredModels = [];

        foreach($this->models as $model){
            if (!in_array($model->UniqueIdentifier, $uniqueIdentifiersToFilter )){
                $filteredModels[] = $model;
            }
        }

        $this->models = $filteredModels;
    }

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
        return ($offset < ($this->count() - $this->filteredIndexCount) && $offset >= 0);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if (in_array($offset, $this->filteredIndexes)){
            return $this->offsetGet($offset+1);
        }

        $object = $this->models[$offset];
        $id = $object->UniqueIdentifier;

        if (isset($this->augmentationData[$id])){
            $object->mergeRawData($this->augmentationData[$id]);
        }

        return $object;
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
        $this->models[$offset] = $value;
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
        if (isset($this->models[$offset])) {
            unset($this->models[$offset]);
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
        return count($this->models) - $this->filteredIndexCount;
    }
}