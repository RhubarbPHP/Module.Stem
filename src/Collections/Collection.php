<?php

namespace Rhubarb\Stem\Collections;

use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Filter;

abstract class Collection implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * The Model class used when spinning out items.
     *
     * @var string
     */
    protected $modelClassName;

    /**
     * The collection of model IDs represented in this collection.
     *
     * @var array
     */
    protected $uniqueIdentifiers = [];

    /**
     * The source of our collection items.
     * 
     * @var CollectionCursor
     */
    private $collectionCursor;

    /**
     * The only or top level group filter to apply to the list.
     *
     * @var Filter
     */
    protected $filter;

    /**
     * True if ranging has been disabled
     *
     * @var bool
     */
    private $rangingDisabled = false;

    private $intersections = [];

    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }

    public final function intersectWith(Collection $collection, $parentColumnName, $childColumnName)
    {
        $this->intersections[] = new Intersection($collection, $parentColumnName, $childColumnName);
    }

    /**
     * Returns the name of the model in our collection.
     *
     * @return string
     */
    final public function getModelClassName()
    {
        return $this->modelClassName;
    }

    /**
     * filter the existing list using the supplied DataFilter.
     *
     * @param  Filter $filter
     * @return $this
     */
    public function filter(Filter $filter)
    {
        if (is_null($this->filter)) {
            $this->filter = $filter;
        } else {
            $this->filter = new AndGroup([$filter, $this->filter]);
        }

        $this->invalidate();

        return $this;
    }

    /**
     * Returns the Filter object being used to filter models for this collection.
     *
     * @return Filter
     */
    final public function getFilter()
    {
        return $this->filter;
    }

    public function disableRanging()
    {
        $this->rangingDisabled = true;
    }

    public function enableRanging()
    {
        $this->rangingDisabled = false;
    }

    private function invalidate()
    {
        $this->collectionCursor = null;
    }

    /**
     * Ensures a cursor has been created.
     */
    private function prepareCursor()
    {
        if ($this->collectionCursor != null){
            // Cursor already exists, we shouldn't bother making a new one.
            return;
        }

        $this->collectionCursor = $this->createCursor();
    }

    protected abstract function createCursor();

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $this->prepareCursor();
        return $this->collectionCursor->current();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->prepareCursor();
        return $this->collectionCursor->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $this->prepareCursor();
        return $this->collectionCursor->key();
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
        $this->prepareCursor();
        return $this->collectionCursor->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->prepareCursor();
        $this->collectionCursor->rewind();
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
        $this->prepareCursor();
        return $this->collectionCursor->offsetExists($offset);
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
        $this->prepareCursor();
        return $this->collectionCursor->offsetGet($offset);
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
        $this->prepareCursor();
        $this->collectionCursor->offsetSet($offset, $value);
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
        $this->prepareCursor();
        $this->collectionCursor->offsetUnset($offset);
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
        $this->prepareCursor();
        return $this->collectionCursor->count();
    }
}