<?php

namespace Rhubarb\Stem\Collections;

use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Exceptions\SortNotValidException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\FloatColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;

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

    /**
     * The collection of intersections to perform.
     *
     * @var Intersection[]
     */
    private $intersections = [];

    /**
     * The collection of aggregate columns.
     *
     * @var Aggregate[]
     */
    private $aggregateColumns = [];

    /**
     * An array of sorting directives.
     *
     * @var array
     */
    private $sorts = [];

    /**
     * An array of column names to group by.
     *
     * @var array
     */
    private $groups = [];

    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }

    public final function addSort($columnName, $ascending = true)
    {
        $sort = new Sort();
        $sort->columnName = $columnName;
        $sort->ascending = $ascending;
        $this->sorts[] = $sort;

        return $this;
    }

    public final function replaceSorts($sorts)
    {
        $this->sorts = [];

        foreach($sorts as $index => $value){
            if ($value instanceof Sort){
                $this->sorts[] = $value;
                continue;
            }

            $sort = new Sort();
            $sort->columnName = $index;
            $sort->ascending = $value;

            $this->sorts[] = $sort;
        }

        return $this;
    }

    /**
     * Create an intersection with a second collection.
     *
     * Only rows matching the conditions will remain in the collection.
     *
     * @param Collection $collection
     * @param string $parentColumnName
     * @param string $childColumnName
     * @param string[] $columnsToPullUp An array of column names in the intersected collection to copy to the parent collection.
     * @return $this
     */
    public final function intersectWith(Collection $collection, $parentColumnName, $childColumnName, $columnsToPullUp = [])
    {
        // Group the intersected collection by the child column:
        $collection->groups[] = $childColumnName;

        $this->intersections[] = new Intersection($collection, $parentColumnName, $childColumnName, $columnsToPullUp);

        return $this;
    }

    /**
     * Adds the instruction to build an aggregate column.
     *
     * Should be used in conjuction with group()
     *
     * @param Aggregate $aggregate
     * @return $this
     */
    public final function addAggregateColumn(Aggregate $aggregate)
    {
        $this->aggregateColumns[] = $aggregate;

        return $this;
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

    final public function getSorts()
    {
        return $this->sorts;
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

        /**
         * Some cursors will be able to perform intersections. Any intersections remaining
         * are handled by filterIntersections()
         */
        foreach($this->intersections as $intersection){
            if (!$intersection->intersected){
                $this->filterIntersection($intersection);
            }
        }

        /**
         * Some cursors will be able to perform the filtering internally. Those that can't
         * will be handled by filterCursor()
         */
        if (!$this->collectionCursor->filtered){
            $this->filterCursor();
        }

        /**
         * Some cursors can handle aggregates. Any aggregates that aren't yet computed are mopped
         * up in processAggregates();
         */
        $aggregatesToProcess = [];
        foreach($this->aggregateColumns as $aggregateColumn){
            if (!$aggregateColumn->calculated){
                $aggregatesToProcess[] = $aggregateColumn;
            }
        }

        if (count($aggregatesToProcess) > 0){
            $this->processAggregates($aggregatesToProcess);
        }

        $this->collectionCursor->rewind();
    }

    private function getGroupKeyForModel(Model $model)
    {
        $key = "";

        foreach($this->groups as $group){
            $key .= $model[$group]."|";
        }

        return $key;
    }

    /**
     * Takes a collection of aggregates and computes their values.
     *
     * @param Aggregate[] $aggregates
     */
    private function processAggregates($aggregates)
    {
        foreach($this->collectionCursor as $model){
            foreach($aggregates as $aggregate){
                $aggregate->calculateByIteration($model, $this->getGroupKeyForModel($model));
            }
        }

        $additionalData = [];

        foreach($aggregates as $aggregate){
            $groups = $aggregate->getGroups();

            foreach($this->collectionCursor as $model){

                $id = $model->UniqueIdentifier;
                $groupKey = $this->getGroupKeyForModel($model);

                if (!isset($additionalData[$id])){
                    $additionalData[$id] = [];
                }

                if (isset($groups[$groupKey])){
                    $additionalData[$id][$aggregate->getAlias()] = $groups[$groupKey];
                }
            }
        }
        
        $this->collectionCursor->setAugmentationData($additionalData);
    }

    private function filterIntersection(Intersection $intersection)
    {
        $childByIntersectColumn = [];

        foreach($intersection->collection as $childModel){
            $childByIntersectColumn[$childModel[$intersection->childColumnName]] = $childModel;
        }

        $uniqueIdsToFilter = [];
        $augmentationData = [];
        $hasColumnsToPullUp = count($intersection->columnsToPullUp);

        foreach($this->collectionCursor as $parentModel){
            $parentValue = $parentModel[$intersection->parentColumnName];
            if (!isset($childByIntersectColumn[$parentValue])){
                $uniqueIdsToFilter[] = $parentModel->uniqueIdentifier;
            } elseif ($hasColumnsToPullUp) {

                $augmentationData[$parentModel->uniqueIdentifier] = [];

                foreach($intersection->columnsToPullUp as $column => $alias){
                    if (is_numeric($column)){
                        $column = $alias;
                    }
                    $augmentationData[$parentModel->uniqueIdentifier][$alias] = $childByIntersectColumn[$parentValue][$column];
                }
            }
        }

        if (count($augmentationData)){
            $this->collectionCursor->setAugmentationData($augmentationData);
        }

        $this->collectionCursor->filterModelsByIdentifier($uniqueIdsToFilter);
    }

    /**
     * Filters the collection manually if it wasn't able to filter itself.
     */
    private function filterCursor()
    {
        $filter = $this->getFilter();

        if ($filter){

            $uniqueIdentifiersToFilter = [];

            foreach($this->collectionCursor as $model){
                if ($filter->shouldFilter($model)){
                    $uniqueIdentifiersToFilter[] = $model->uniqueIdentifier;
                }
            }

            $this->collectionCursor->filterModelsByIdentifier($uniqueIdentifiersToFilter);
        }
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