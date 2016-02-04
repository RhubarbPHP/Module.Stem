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

namespace Rhubarb\Stem\Collections;

require_once __DIR__ . "/../Schema/SolutionSchema.php";

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Exceptions\AggregateNotSupportedException;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Implements a collection of model objects that can be filtered and iterated.
 *
 * Note this class couldn't be called "List" as list is a reserved word in php
 */
class Collection implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * The name of the modelling class to use,
     *
     * @var
     */
    protected $modelClassName;

    /**
     * True if the collection has been fetched from the repository.
     *
     * @var bool
     */
    private $fetched = false;

    /**
     * The collection of unique identifiers comprising the list.
     *
     * @var array
     */
    private $uniqueIdentifiers = [];

    /**
     * An array of names of navigation properties we will suggest to the repository should be auto hydrated.
     *
     * @var array
     */
    protected $relationshipNavigationPropertiesToAutoHydrate = [];

    /**
     * The filter to use when fetching the list.
     *
     * @var Filter
     */
    private $filter;

    /**
     * A collection of aggregates to compute for the collection.
     *
     * @var Aggregate[]
     */
    private $aggregates = [];

    /**
     * A list of aggregates that need to be solved by iteration as models are pulled from the collection.
     *
     * @var Aggregate[]
     */
    private $aggregatesNeedingIterated = [];

    public function __construct($modelClassName)
    {
        $this->modelClassName = SolutionSchema::getModelClass($modelClassName);
    }

    public function addAggregateColumn(Aggregate $aggregate)
    {
        $columnName = $aggregate->getAggregateColumnName();

        if (strpos($columnName, ".") === false) {
            throw new AggregateNotSupportedException("Sorry, addAggregateColumn requires that the aggregate operate on a one-to-many relationship property");
        }

        $parts = explode(".", $columnName);

        $relationship = $parts[0];

        // Aggregate Columns must be added on properties that are one to many relationships as we need
        // to put group bys into the query.
        $relationships = SolutionSchema::getAllRelationshipsForModel($this->getModelClassName());

        if (!isset($relationships[$relationship])) {
            throw new AggregateNotSupportedException("Sorry, addAggregateColumn requires that the aggregate operate on a one-to-many relationship property");
        }

        if (!($relationships[$relationship] instanceof OneToMany)) {
            throw new AggregateNotSupportedException("Sorry, addAggregateColumn requires that the aggregate operate on a one-to-many relationship property");
        }

        $this->aggregates[] = $aggregate;

        return $this;
    }

    /**
     * Returns an array of the collection items.
     *
     * For large collections this is _VERY_ expensive and so should only be used for small
     * collections < 100 items.
     *
     * @return array
     */
    public function toArray()
    {
        $items = [];

        foreach ($this as $item) {
            $items[] = $item;
        }

        return $items;
    }

    public function getAggregates()
    {
        return $this->aggregates;
    }

    /**
     * Deletes all items in the collection from the repository
     */
    public function deleteAll()
    {
        foreach ($this as $item) {
            $item->delete();
        }
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
     * Returns a DataFilter used to filter records for this list.
     *
     * @return Filter
     */
    final public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Returns the collection of sorting options in current use.
     *
     * @return array
     */
    final public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * Return's the schema object created by the model.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    final public function getModelSchema()
    {
        $repository = $this->getRepository();
        $schema = $repository->getModelSchema();

        return $schema;
    }

    public function autoHydrate($relationshipNavigationPropertyName)
    {
        // Note, the unit test for this is in the MySqlTest test case as we require a repository that
        // supports auto hydration.

        if (!in_array($relationshipNavigationPropertyName, $this->relationshipNavigationPropertiesToAutoHydrate)) {
            $this->relationshipNavigationPropertiesToAutoHydrate[] = $relationshipNavigationPropertyName;
        }

        return $this;
    }

    public function getRepositoryFetchCommand(&$namedParams = null)
    {
        $repository = $this->getRepository();

        return $repository->getRepositoryFetchCommandForDataList($this, $this->relationshipNavigationPropertiesToAutoHydrate, $namedParams);
    }

    /**
     * Hydrates the list with the necessary unique identifiers.
     */
    public function fetchList()
    {
        if ($this->fetched) {
            return;
        }

        $this->fetched = true;

        // Instantiate an empty instance of the data object class name.
        $repository = $this->getRepository();

        $this->unfetchedRowCount = 0;

        $this->uniqueIdentifiers = $repository->getUniqueIdentifiersForDataList(
            $this,
            $this->unfetchedRowCount,
            $this->relationshipNavigationPropertiesToAutoHydrate
        );
        $this->iterator = 0;

        if ($this->filter !== null) {
            $uniqueIdentifiersToFilter = $this->filter->getUniqueIdentifiersToFilter($this);
            $this->uniqueIdentifiers = array_values(array_diff($this->uniqueIdentifiers, $uniqueIdentifiersToFilter));

            $this->iterator = 0;
        }

        $this->aggregatesNeedingIterated = [];

        // Build a list of aggregates that need executed as models are pulled from the collection. These will be
        // the aggregates that could not be handled by the repository.
        foreach ($this->aggregates as $aggregate) {
            if (!$aggregate->wasAggregatedByRepository()) {
                $this->aggregatesNeedingIterated[] = $aggregate;
            }
        }

        if (sizeof($this->sorts)) {
            $sortedIdentifiers = $repository->getSortedUniqueIdentifiersForDataList($this);

            if ($sortedIdentifiers != null) {
                $this->uniqueIdentifiers = $sortedIdentifiers;
            }
        }

        $this->rewind();
    }

    /**
     * @param Aggregate|Aggregate[] $aggregates
     * @return array
     */
    final public function calculateAggregates($aggregates)
    {
        $args = func_get_args();

        if (sizeof($args) > 1) {
            $aggregates = $args;
        }

        if (!is_array($aggregates)) {
            $aggregates = [$aggregates];
        }

        $repository = $this->getRepository();

        $results = $repository->calculateAggregates($aggregates, $this);

        if ($results === false || $results === null || sizeof($results) == 0) {
            for ($x = 0; $x < sizeof($aggregates); $x++) {
                $results[] = null;
            }
        }

        foreach ($results as $key => $value) {
            if ($value === null) {
                $aggregate = $aggregates[$key];

                $results[$key] = $aggregate->calculateByIteration($this);
            }
        }

        return $results;
    }

    /**
     * Get's the repository used by the associated data object.
     *
     * @return \Rhubarb\Stem\Repositories\Repository
     */
    protected function getRepository()
    {
        $emptyObject = SolutionSchema::getModel($this->modelClassName);

        $repository = $emptyObject->getRepository();

        return $repository;
    }

    /**
     * Looks for and returns the model with the unique identifier if it exists in the collection.
     *
     * If the model can not be found, an RecordNotFoundException is thrown.
     *
     * It is good practice to use this method instead of instantiating models directly to ensure
     * users don't manipulate URLs to reveal objects they shouldn't be able to access.
     *
     * @param  $uniqueIdentifier
     * @return bool
     * @throws \Rhubarb\Stem\Exceptions\RecordNotFoundException
     */
    public function findModelByUniqueIdentifier($uniqueIdentifier)
    {
        $oldFilter = $this->filter;

        $schema = $this->getModelSchema();
        $column = $schema->uniqueIdentifierColumnName;

        $this->filter(new Equals($column, $uniqueIdentifier));

        $result = false;

        if (count($this) > 0) {
            $result = $this[0];
        }

        $this->filter = $oldFilter;
        $this->invalidateList();

        if (!$result) {
            throw new RecordNotFoundException($this->modelClassName, $uniqueIdentifier);
        }

        return $result;
    }

    /**
     * Returns true if the collection contains the model with the requested unique identifier
     *
     * @param $uniqueIdentifier
     */
    public function containsUniqueIdentifier($uniqueIdentifier)
    {
        try {
            $this->findModelByUniqueIdentifier($uniqueIdentifier);
            return true;
        } catch (RecordNotFoundException $er) {
        }

        return false;
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

        $this->invalidateList();

        return $this;
    }

    /**
     * Applies the provided set of property values to all of the models in the collection.
     *
     * Where repository specific optimisation is available this will be leveraged to run the batch
     * update at the data source rather than iterating over the items.
     *
     * @param  Array $propertyPairs       An associative array of key value pairs to update
     * @param  bool  $fallBackToIteration If the repository can't perform the action directly, perform the update by iterating over all the models in the collection. You should only pass true if you know that the collection doesn't meet the criteria for an optimised update and the iteration of items won't cause problems
     *                                  iterating over all the models in the collection. You should only pass true
     *                                  if you know that the collection doesn't meet the criteria for an optimised
     *                                  update and the iteration of items won't cause problems
     * @return Collection The original collection returned for chaining
     * @throws BatchUpdateNotPossibleException Thrown if the repository for the collection can't perform the update,
     *                                         and $fallBackToIteration is false.
     */
    public function batchUpdate($propertyPairs, $fallBackToIteration = false)
    {
        try {
            $this->getRepository()->batchCommitUpdatesFromCollection($this, $propertyPairs);
        } catch (BatchUpdateNotPossibleException $er) {
            if ($fallBackToIteration) {
                foreach ($this as $item) {
                    $item->mergeRawData($propertyPairs);
                    $item->save();
                }
            } else {
                throw $er;
            }
        }

        return $this;
    }

    public function replaceFilter(Filter $filter)
    {
        $this->filter = $filter;

        $this->invalidateList();

        return $this;
    }


    /**
     * filter the existing list using a not filter based on the supplied DataFilter.
     *
     * @param Filter $filter
     */
    public function not(Filter $filter)
    {
        $this->filter = new \Rhubarb\Stem\Filters\Not($filter);

        $this->invalidateList();
    }

    /**
     * Invalidates the list to make sure it is fetched again with up to date data.
     */
    public function invalidateList()
    {
        $this->uniqueIdentifiers = [];
        $this->fetched = false;
        $this->iterator = -1;
    }


    /**
     * append a model to the list and correctly set any fields required to make this re-fetchable through the same list.
     *
     * @param  \Rhubarb\Stem\Models\Model $model
     * @return \Rhubarb\Stem\Models\Model|null
     */
    public function append(Model $model)
    {
        $result = null;

        // If the list was filtered make sure that value is set on the model.
        if ($this->filter !== null) {
            $result = $this->filter->setFilterValuesOnModel($model);
        }

        $model->save();

        // Make sure the list has been fetched so we can pop the unique identifier on the end.
        if ($this->fetched) {
            $this->uniqueIdentifiers[] = $model->UniqueIdentifier;
        }

        return ($result === null) ? $model : $result;
    }

    ////////////////////////////////////////////////////////
    //// Interface methods
    ////////////////////////////////////////////////////////

    private $iterator = -1;

    public function current()
    {
        return $this[$this->iterator];
    }

    public function next()
    {
        $this->iterator++;
    }

    public function key()
    {
        return $this->uniqueIdentifiers[$this->iterator];
    }

    public function valid()
    {
        return ($this->offsetExists($this->iterator));
    }

    public function rewind()
    {
        $this->iterator = 0;
    }

    /**
     * Returns the unique identifier at the given offset bearing in mind any range specific functionality
     *
     * @param $offset
     */
    protected function getUniqueIdentifierAtOffset($offset)
    {
        $index = $offset;

        if (!$this->rangingDisabled && !$this->unfetchedRowCount) {
            $index = $offset + $this->rangeStartIndex;
        }

        return $this->uniqueIdentifiers[$index];
    }

    public function offsetExists($offset)
    {
        $rangeEnd = $count = $this->count();

        if (!$this->rangingDisabled) {
            $rangeEnd = ($this->rangeEndIndex !== null) ? min(
                $this->rangeEndIndex + 1,
                $count
            ) - $this->rangeStartIndex : $count;
        }

        return ($offset >= 0 && $offset < $rangeEnd);
    }

    public function offsetGet($offset)
    {
        $this->fetchList();

        $class = $this->modelClassName;

        $model = new $class($this->getUniqueIdentifierAtOffset($offset));

        foreach ($this->aggregatesNeedingIterated as $aggregate) {
            $parts = explode(".", $aggregate->getAggregateColumnName());
            $relationship = $parts[0];

            $collection = $model[$relationship];

            $alias = $aggregate->getAlias();

            $model[$alias] = $aggregate->calculateByIteration($collection);
        }

        return $model;
    }

    public function offsetSet($offset, $value)
    {
        throw new \Rhubarb\Crown\Exceptions\ImplementationException("Can't set items of a list.");
    }

    public function offsetUnset($offset)
    {
        throw new \Rhubarb\Crown\Exceptions\ImplementationException("Can't unset items of a list.");
    }

    public function count()
    {
        if ($this->fetched) {
            return sizeof($this->uniqueIdentifiers) + $this->unfetchedRowCount;
        }

        $modelSchema = $this->getModelSchema();
        list($count) = $this->calculateAggregates(new Count($modelSchema->uniqueIdentifierColumnName));
        return $count;
    }

    private $sorts = [];

    /**
     * Adds a new column to the sort list.
     *
     * @param  $columnName
     * @param  bool       $ascending
     * @return $this
     */
    public function addSort($columnName, $ascending = true)
    {
        $this->sorts[$columnName] = $ascending;

        $this->invalidateList();

        return $this;
    }

    /**
     * Replaces the sort list with a new collection.
     *
     * This should be an associative array of column name to bool (true = ASC, false = DESC) pairs OR
     * for a single sort a column name and direction as two separate params
     *
     * @param  string|array $sortDetails
     * @param  null|bool    $sortDirection
     * @return $this
     */
    public function replaceSort($sortDetails, $sortDirection = null)
    {
        if (is_array($sortDetails)) {
            $this->sorts = $sortDetails;
        } else {
            $this->sorts = [];
            $this->addSort($sortDetails, ($sortDirection === null) ? true : (bool)$sortDirection);
        }

        $this->invalidateList();

        return $this;
    }

    private $rangeStartIndex = 0;
    private $rangeEndIndex = null;
    private $unfetchedRowCount = 0;
    private $rangingDisabled = false;

    public function disableRanging()
    {
        $this->rangingDisabled = true;
    }

    public function enableRanging()
    {
        $this->rangingDisabled = false;
    }

    /**
     * Limits the range of iteration from startIndex to startIndex + maxItems
     *
     * This can be used by the repository to employ limits but generally allows for easy paging of a list.
     *
     * @param  int $startIndex
     * @param  int $maxItems
     * @return $this
     */
    public function setRange($startIndex, $maxItems)
    {
        $changed = false;

        if (sizeof($this->sorts) == 0) {
            $this->addSort($this->getModelSchema()->uniqueIdentifierColumnName);
        }

        if ($this->rangeStartIndex != $startIndex) {
            $this->rangeStartIndex = $startIndex;
            $changed = true;
        }

        if ($this->rangeEndIndex != $startIndex + $maxItems - 1) {
            $this->rangeEndIndex = $startIndex + $maxItems - 1;
            $changed = true;
        }

        if ($changed) {
            // Ranges can often be reset to the same values in which case we don't want to invalidate the list
            // as that would cause another query being sent to the database.
            $this->invalidateList();
        }

        return $this;
    }

    /**
     * Returns the range in use on this list as a two index array of start and count.
     *
     * Returns false if no range is in use.
     */
    public function getRange()
    {
        if ($this->rangeEndIndex === null) {
            return false;
        }

        return [$this->rangeStartIndex, $this->rangeEndIndex - $this->rangeStartIndex + 1];
    }

    public function getSerializableForm($columns = [])
    {
        $results = [];

        foreach ($this as $item) {
            $results[] = $item->getSerializableForm($columns);
        }

        return $results;
    }
}
