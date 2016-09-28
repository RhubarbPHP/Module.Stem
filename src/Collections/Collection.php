<?php

namespace Rhubarb\Stem\Collections;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Exceptions\BatchUpdateNotPossibleException;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Exceptions\SortNotValidException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\PdoRepository;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\FloatColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;

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
     * @var CollectionJoin[]
     */
    private $intersections = [];

    /**
     * If this is an intersection collection this will be true to signify it has a parent.
     *
     * @var null
     */
    private $isIntersection = false;

    /**
     * The collection of aggregate columns.
     *
     * @var Aggregate[]
     */
    private $aggregateColumns = [];

    /**
     * An array of sorting directives.
     *
     * @var Sort[]
     */
    private $sorts = [];

    /**
     * An array of column names to group by.
     *
     * @var array
     */
    private $groups = [];

    /**
     * When building complex intersection relationships it's important that a collection can be
     * represented by a unique name (as multiple instances of the collection could be involed)
     *
     * @var string
     * @see getUniqueReference()
     */
    private $uniqueReference;

    private $rangeApplied = false;

    /**
     * Columns that are being added to the collection to serve the purposes of aggregates or intersections
     * are listed here.
     *
     * @var array
     */
    private $aliasedColumns = [];

    /**
     * A map for aliases to the unique reference of their source collection
     *
     * @var array
     */
    private $aliasedColumnsToCollection = [];

    /**
     * Columns that are being added to the collection to serve the purposes of aggregates pulled up from intersections
     * are listed here.
     *
     * @var array
     */
    private $pulledUpAggregatedColumns = [];

    /**
     * The starting row for iteration
     * @var int
     */
    private $rangeStartIndex = 0;

    /**
     * The ending row for iteration.
     *
     * Null denotes no end.
     *
     * @var null|int
     */
    private $rangeEndIndex = null;

    /**
     * A list of all unique references already in use.
     *
     * Used by some repository providers to guarantee unique table names during complex interesections.
     *
     * @var string[]
     */
    private static $uniqueReferencesUsed = [];

    /**
     * True if intersections can cause multiple models to be returned with the same unique identifier
     *
     * @var bool
     */
    private $allowDuplicates = false;

    /**
     * Stores a list of column types for pull ups from intersections.
     *
     * @var Column[]
     */
    public $additionalColumns = [];

    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }

    /**
     * Sets if intersections can cause multiple models to be returned with the same unique identifier
     *
     * @param bool $allowDuplicates True if intersections can cause multiple models to be returned with the same unique identifier
     */
    public function setAllowDuplicates($allowDuplicates)
    {
        $this->allowDuplicates = $allowDuplicates;

        return $this;
    }
    /**
     * Clears the unique reference counters.
     *
     * Used only by unit testing to guarantee predictable SQL generation.
     */
    public static function clearUniqueReferencesUsed()
    {
        Collection::$uniqueReferencesUsed = [];
    }

    /**
     * Gets the unique reference for this collection.
     *
     * @return string
     */
    public function getUniqueReference()
    {
        if ($this->uniqueReference === null) {

            $modelName = basename(str_replace("\\", "/", $this->getModelClassName()));

            if (!isset(self::$uniqueReferencesUsed[$modelName])){
                self::$uniqueReferencesUsed[$modelName] = 0;
            }

            self::$uniqueReferencesUsed[$modelName]++;

            $this->uniqueReference = $modelName.(self::$uniqueReferencesUsed[$modelName]>1 ? self::$uniqueReferencesUsed[$modelName] : "");
        }

        return $this->uniqueReference;
    }

    /**
     * Gets the list of aliased columns
     * @return array
     */
    public function getAliasedColumns()
    {
        return $this->aliasedColumns;
    }

    /**
     * Gets the mapping of aliased columns to their source collection.
     * @return array
     */
    public function getAliasedColumnsToCollection()
    {
        return $this->aliasedColumnsToCollection;
    }

    /**
     * Gets the names of aggregate columns pulled up from intersected collections.
     * @return array
     */
    public function getPulledUpAggregatedColumns()
    {
        return $this->pulledUpAggregatedColumns;
    }

    /**
     * Get's the repository used by the associated data object.
     *
     * @return \Rhubarb\Stem\Repositories\Repository
     */
    public function getRepository()
    {
        $emptyObject = SolutionSchema::getModel($this->modelClassName);

        $repository = $emptyObject->getRepository();

        return $repository;
    }

    /**
     * Gets the model's schema.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    public function getModelSchema()
    {
        return $this->getRepository()->getModelSchema();
    }

    /**
     * Adds a group to the collection.
     *
     * @param $columnName
     * @return $this
     */
    public final function addGroup($columnName)
    {
        $this->groups[] = $columnName;

        return $this;
    }

    /**
     * Adds another sort condition.
     *
     * @param string $columnName The name of the column to sort on.
     * @param bool $ascending True to sort ascending
     * @return $this
     * @throws FilterNotSupportedException
     */
    public final function addSort($columnName, $ascending = true)
    {
        $parts = explode(".", $columnName);

        $sort = new Sort();

        if (count($parts) > 1) {
            $columnName = $parts[count($parts) - 1];

            $relationships = array_slice($parts, 0, count($parts) - 1);
            $newColumnName = PdoRepository::getPdoParamName($columnName);
            $intersectionCollection = $this->createIntersectionForRelationships($relationships,
                [$columnName => $newColumnName]);
            $sort->tableAlias = $intersectionCollection->getUniqueReference();
            $sort->alias = $newColumnName;
        } else {
            $sort->alias = $columnName;
        }

        $sort->columnName = $columnName;
        $sort->ascending = $ascending;
        $this->sorts[] = $sort;

        $this->invalidate();

        return $this;
    }

    /**
     * Removes current sorts and replaces with another.
     *
     * Can take an array of sorts instead of a single sort in which case it should be an associative array
     * of column name => true/false values (true for ascending false for descending)
     *
     * @param $columnName
     * @param bool $ascending
     * @return $this
     */
    public final function replaceSort($columnName, $ascending = true)
    {
        if (!is_array($columnName)) {
            $sorts = [$columnName => $ascending];
        } else {
            $sorts = $columnName;
        }

        $this->sorts = [];

        foreach ($sorts as $index => $value) {
            $this->addSort($index, $value);
        }

        return $this;
    }

    /**
     * Append a model to the list and correctly set any fields required to make this re-fetchable through the same list.
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

        // Make sure the list is refetched.
        $this->invalidate();

        return ($result === null) ? $model : $result;
    }

    /**
     * Create an intersection with a second collection.
     *
     * Only rows found where the value of $sourceColumnName is found in $intersectedColumnName of
     * $collection will remain in this collection.
     *
     * Not that if the collection contains aggregate columns but isn't yet grouped, we will automatically
     * add a group to the collection using the $intersectedColumnName
     *
     * @param Collection $collection The collection to intersect with
     * @param string $sourceColumnName The name of the column in the parent collection to intersect using.
     * @param string $targetColumnName The name of the column in the child collection to intersect using
     * @param string[] $columnsToPullUp An array of column names in the intersected collection to copy upwards
     *                                  into the rows of the parent collection.
     * @param bool $autoHydrate A hint that you want to auto hydrate this relationship. Auto hydration ensures that
     *                          the data in the intersected repository are loaded and ready for use by modelling.
     *                          If your repository supports it this is often very efficient if you will need this
     *                          data when processing the collection. Without auto hydration secondary hits on the
     *                          repository will be unavoidable.
     * @return $this
     */
    public final function intersectWith(Collection $collection, $sourceColumnName, $targetColumnName, $columnsToPullUp = [], $autoHydrate = false)
    {
        $collection->isIntersection = true;
        $collectionJoin = new CollectionJoin($collection, $sourceColumnName, $targetColumnName, $columnsToPullUp, $autoHydrate, CollectionJoin::JOIN_TYPE_INTERSECTION);
        $this->intersections[] = $collectionJoin;
        return $this->prepareCollectionJoin($collectionJoin);
    }

    /**
     * Join a collection on to our parent collection
     *
     * All rows from our parent collection remain, and, where the value of $sourceColumnName equals the
     * value of $targetColumnName, the rows from the child collection will be attached. If there is no match
     * blank values will be joined.
     *
     * @param Collection $collection The child collection
     * @param string $sourceColumnName The name of the column in the parent collection to attach using
     * @param string $targetColumnName The name of the column in the child collection to attach using
     * @param array $columnsToPullUp An array of column names in the child collection to copy upwards
     *                               into the rows of the parent collection.
     * @param bool $autoHydrate A hint that you want to auto hydrate this relationship. Auto hydration ensures that
     *                          the data in the intersected repository are loaded and ready for use by modelling.
     *                          If your repository supports it this is often very efficient if you will need this
     *                          data when processing the collection. Without auto hydration secondary hits on the
     * @return Collection
     */
    public function joinWith(Collection $collection, $sourceColumnName, $targetColumnName, $columnsToPullUp = [], $autoHydrate = false)
    {
        $collectionJoin = new CollectionJoin($collection, $sourceColumnName, $targetColumnName, $columnsToPullUp,
            $autoHydrate, CollectionJoin::JOIN_TYPE_ATTACH);
        $this->intersections[] = $collectionJoin;
        return $this->prepareCollectionJoin($collectionJoin);
    }

    private function prepareCollectionJoin(CollectionJoin $collectionJoin)
    {
        $collection = $collectionJoin->collection;
        $columnsToPullUp = $collectionJoin->columnsToPullUp;
        $targetColumnName = $collectionJoin->targetColumnName;

        $childAggregates = $collection->getAggregateColumns();

        // If we're aggregating on the collection join, we need to group by. Aggregates used without group bys cause
        // meaningless data in the best case and (depending on the repository) an error in the worst case.
        if (count($childAggregates) > 0 && count($collection->groups) == 0) {
            $collection->groups[] = $targetColumnName;
        }

        foreach ($columnsToPullUp as $column => $alias) {

            if (is_numeric($column)) {
                $column = $alias;
            }

            $this->aliasedColumns[$alias] = $column;
            $this->aliasedColumnsToCollection[$column] = $collection->getUniqueReference();

            foreach ($childAggregates as $aggregate) {
                if ($aggregate->getAlias() == $column) {
                    $this->pulledUpAggregatedColumns[] = $column;
                }
            }
        }

        $this->invalidate();
        $collection->invalidate();

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
        $parts = explode(".", $aggregate->getAggregateColumnName());

        if (count($parts) > 1) {
            $columnName = $parts[count($parts) - 1];

            $relationships = array_slice($parts, 0, count($parts) - 1);

            $aggregate->setAliasDerivedColumn(str_replace(".", "", $aggregate->getAggregateColumnName()));
            $aggregate->setAggregateColumnName($columnName);

            $collection = $this->createIntersectionForRelationships($relationships, [$aggregate->getAlias()]);
            $collection->addAggregateColumn($aggregate);
            return $this;
        }

        $this->aggregateColumns[] = $aggregate;
        $this->invalidate();

        return $this;
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

        $oldAggregates = $this->aggregateColumns;
        $this->aggregateColumns = [];

        foreach ($aggregates as $aggregate) {
            $this->addAggregateColumn($aggregate);
        }

        $this->invalidate();

        $results = [];

        $hasDataToAggregate = $this->count() > 0;
        foreach ($aggregates as $aggregate) {
            $results[] = $hasDataToAggregate ? $this[0][$aggregate->getAlias()] : null;
        }

        $this->aggregateColumns = $oldAggregates;

        $this->invalidate();

        return $results;
    }

    /**
     * Applies the provided set of property values to all of the models in the collection.
     *
     * Where repository specific optimisation is available this will be leveraged to run the batch
     * update at the data source rather than iterating over the items.
     *
     * @param  array $propertyPairs An associative array of key value pairs to update
     * @param  bool $fallBackToIteration If the repository can't perform the action directly, perform the update by iterating over all the models in the collection. You should only pass true if you know that the collection doesn't meet the criteria for an optimised update and the iteration of items won't cause problems
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

    /**
     * Returns the list of aggregate columns.
     *
     * @return \Rhubarb\Stem\Aggregates\Aggregate[]
     */
    public final function getAggregateColumns()
    {
        return $this->aggregateColumns;
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
     * Replaces the filter on the collection completely rather than blending it as filter() does
     *
     * @param Filter $filter
     * @return $this
     */
    public function replaceFilter(Filter $filter)
    {
        $this->filter = $filter;
        $this->invalidate();

        return $this;
    }

    public function clearFilter()
    {
        $this->filter = null;
        $this->invalidate();

        return $this;
    }


    /**
     * filter the existing list using the supplied DataFilter.
     *
     * @param  Filter $filter
     * @return $this
     */
    public function filter(...$filters)
    {
        if (sizeof($filters) == 0) {
            return;
        }

        $andGroup = new AndGroup();

        if (is_array($filters[0])) {
            $filters = $filters[0];
        }

        foreach ($filters as $filter) {
            if (!($filter instanceof Filter)) {
                throw new FilterNotSupportedException('One or more of the parameters, or one or more elements of an array parameter, was not of the Filter Class');
            }

            $andGroup->addFilters($filter);
        }

        if (sizeof($filters) == 1) {
            $andGroup = $filters[0];
        }

        if (is_null($this->filter)) {
            $this->filter = $andGroup;
        } else {
            $this->filter = new AndGroup([$andGroup, $this->filter]);
        }

        $this->invalidate();

        return $this;
    }

    /**
     * Disables ranging on this collection
     */
    public function disableRanging()
    {
        $this->rangingDisabled = true;
    }

    /**
     * Enables ranging on this collection
     */
    public function enableRanging()
    {
        $this->rangingDisabled = false;
    }

    /**
     * Gets the first row number to select.
     *
     * @return int
     */
    public function getRangeStart()
    {
        return $this->rangeStartIndex;
    }

    /**
     * Gets the last row number to select.
     * @return null
     */
    public function getRangeEnd()
    {
        return $this->rangeEndIndex;
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
            $this->addSort(SolutionSchema::getModelSchema($this->getModelClassName())->uniqueIdentifierColumnName);
        }

        if ($this->rangeStartIndex != $startIndex) {
            $this->rangeStartIndex = $startIndex;
            $changed = true;
        }

        if ($this->rangeEndIndex !== $startIndex + $maxItems - 1) {
            $this->rangeEndIndex = $startIndex + $maxItems - 1;
            $changed = true;
        }

        if ($changed) {
            // Ranges can often be reset to the same values in which case we don't want to invalidate the list
            // as that would cause another query being sent to the database.
            $this->invalidate();
        }

        return $this;
    }

    /**
     * Flags this collections has having had a range applied.
     */
    final public function markRangeApplied()
    {
        $this->rangeApplied = true;
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

    final public function getGroups()
    {
        return $this->groups;
    }

    final public function getIntersections()
    {
        return $this->intersections;
    }

    private function invalidate()
    {
        $this->rangeApplied = false;
        $this->collectionCursor = null;
    }

    /**
     * Creates an intersection for the given relationship.
     *
     * @param $relationshipName
     * @throws FilterNotSupportedException
     */
    public function autoHydrate($relationshipName)
    {
        $this->createIntersectionForRelationships([$relationshipName]);
    }

    /**
     * Builds a chain of intersections that follow the chain of relationships passed in $relationshipsNeeded.
     *
     * For example if you had a Contact model which was related to a Company model which was related to a
     * Category model (Contact->Company->Category), and your starting collection was a Contacts collection you
     * would call createIntersectionForRelationships(["Company", "Category"]);
     *
     * This function is used to provide for filtering and sorting on "dot notation" column expressions e.g.
     *
     * "Company.Category.CategoryName"
     *
     * @param string[] $relationshipsNeeded The names of the relationships to intersect with.
     * @param string[] $pullUps An array of columns to pull up to the top, e.g. CategoryName
     * @return Collection|RepositoryCollection The last collection created, i.e. the most heavily nested one.
     * @throws FilterNotSupportedException Thrown if any of the models don't have an applicable relationship name.
     */
    private function createIntersectionForRelationships($relationshipsNeeded, $pullUps = [])
    {
        $collection = $this;

        foreach ($relationshipsNeeded as $relationshipPropertyName) {
            $relationships = SolutionSchema::getAllRelationshipsForModel($collection->getModelClassName());

            if (!isset($relationships[$relationshipPropertyName])) {
                throw new FilterNotSupportedException("The column couldn't be expanded to intersections");
            }

            $relationship = $relationships[$relationshipPropertyName];

            if ($relationship instanceof OneToMany || $relationship instanceof OneToOne) {
                $targetModel = $relationship->getTargetModelName();
                $parentColumn = $relationship->getSourceColumnName();
                $childColumn = $relationship->getTargetColumnName();

                $autoHydrate = ($collection == $this);

                $collection->intersectWith(
                    $newCollection = new RepositoryCollection($targetModel),
                    $parentColumn,
                    $childColumn,
                    $pullUps,
                    $autoHydrate
                );

                $newCollection->groups[] = $childColumn;

                $collection = $newCollection;
            }
        }

        return $collection;
    }

    /**
     * Create a cursor for the collection if one has not been already.
     *
     * If the repository cannot handle any feature of the collection this function will 'polyfill' support.
     */
    private function prepareCursor()
    {
        if ($this->collectionCursor != null) {
            // Cursor already exists, we shouldn't bother making a new one.
            return;
        }

        // Before we prepare the cursor we should ask all of our filters, sorts and aggregates to
        // check if they have any dot notations that need expanded into intersections.

        $createIntersectionCallback = function ($intersectionsNeeded) {
            return $this->createIntersectionForRelationships($intersectionsNeeded);
        };

        $filter = $this->getFilter();

        if ($filter) {
            try {
                $filter->checkForRelationshipIntersections($this, $createIntersectionCallback);
            } catch (CreatedIntersectionException $ex) {
                $this->filter = null;
            }
        }

        // If we have intersections AND we are the top most statement we need to protect against getting
        // multiple occurrences of our models in the collection so we add a group by on our unique identifier.

        if (count($this->intersections) > 0 && !$this->isIntersection) {
            $uniqueIdentifier = $this->getModelSchema()->uniqueIdentifierColumnName;
            if (!in_array($uniqueIdentifier, $this->groups) && !$this->allowDuplicates) {
                $this->groups[] = $uniqueIdentifier;
            }
        }

        $this->collectionCursor = $this->createCursor();

        /**
         * Some cursors will be able to perform intersections. Any intersections remaining
         * are handled by filterIntersections()
         */
        foreach ($this->intersections as $collectionJoin) {
            if (!$collectionJoin->intersected) {
                $this->filterCollectionJoin($collectionJoin);
            }
        }

        /**
         * Some cursors will be able to perform the filtering internally. Those that can't
         * will be handled by filterCursor()
         */
        if (!$this->collectionCursor->filtered) {
            Log::warning("A collection for ".$this->getModelClassName()." was not filtered completely in a repository. Performance may degrade significantly.", "STEM");
            $this->filterCursor();
        }

        /**
         * Some cursors can handle aggregates. Any aggregates that aren't yet computed are mopped
         * up in processAggregates();
         */
        $aggregatesToProcess = [];
        foreach ($this->aggregateColumns as $aggregateColumn) {
            if (!$aggregateColumn->calculated) {
                $aggregatesToProcess[] = $aggregateColumn;
            }
        }

        if (count($aggregatesToProcess) > 0) {
            $this->processAggregates($aggregatesToProcess);
        }

        $this->collectionCursor->deDupe();

        if (!$this->collectionCursor->grouped) {
            Log::warning("A collection for "
                . $this->getModelClassName()
                . " was not grouped in a repository. Performance may degrade significantly.", "STEM");
            $this->reduceCursorForGroups();
        }

        /** Some cursors handle sorts. Any that couldn't be handled are processed here */
        $sortsToProcess = [];
        foreach ($this->sorts as $sort) {
            if (!$sort->sorted) {
                $sortsToProcess[] = $sort;
                $this->processSorts($sortsToProcess);
            }
        }

        /**
         * Some cursors can't handle ranging on their own. If we have more rows than we should have we
         * wrap the cursor in the RangeLimitedCursor to supply the required behaviour.
         */

        if (!$this->rangeApplied && ($this->rangeStartIndex > 0 || $this->rangeEndIndex !== null)) {
            $augmentationData = $this->collectionCursor->getAugmentationData();
            $this->collectionCursor = new RangeLimitedCursor(
                $this->collectionCursor,
                $this->rangeStartIndex,
                $this->rangeEndIndex);
            $this->collectionCursor->setAugmentationData($augmentationData);
        }

        $this->collectionCursor->rewind();
    }

    /**
     * @param Sort[] $sorts
     * @throws SortNotValidException
     */
    private function processSorts($sorts)
    {
        $class = $this->getModelClassName();
        $schema = SolutionSchema::getModelSchema($class);
        $columns = $schema->getColumns();

        $ids = [];
        $arrays = [];
        $directions = [];
        $types = [];

        $this->disableRanging();

        $sorts = $this->getSorts();
        $firstPass = true;

        foreach ($sorts as $sort) {

            $columnName = $sort->alias;
            $ascending = $sort->ascending;

            $arrays[$columnName] = [];

            $type = SORT_STRING;

            $column = null;

            if (isset($columns[$columnName])) {
                $column = $columns[$columnName];

                if ($column instanceof IntegerColumn || $column instanceof FloatColumn) {
                    $type = SORT_NUMERIC;
                } elseif ($column instanceof DateColumn) {
                    $type = SORT_REGULAR;
                }
            } else {
                $type = SORT_REGULAR;
            }

            $types[$columnName] = $type;
            $directions[$columnName] = ($ascending) ? SORT_ASC : SORT_DESC;

            $totalCount = 0;

            foreach ($this->collectionCursor as $item) {
                if (!isset($item[$columnName])) {
                    throw new SortNotValidException($columnName);
                } else {
                    $itemValue = $item[$columnName];
                }

                $arrays[$columnName][$totalCount] = $itemValue;
                $totalCount++;

                if ($firstPass) {
                    $ids[] = $item->getUniqueIdentifier();
                }
            }

            $firstPass = false;
        }

        $this->enableRanging();

        if (sizeof($arrays)) {
            $params = [];

            foreach ($arrays as $column => $data) {
                $params[] = &$arrays[$column];
                $params[] = $directions[$column];
                $params[] = $types[$column];
            }

            $params[] = &$ids;

            call_user_func_array("array_multisort", $params);
        }

        // Switch to the unique identifer list cursor now we have a set list of ids.
        $augmentationData = $this->collectionCursor->getAugmentationData();
        $this->collectionCursor = new UniqueIdentifierListCursor($ids, $class);
        $this->collectionCursor->setAugmentationData($augmentationData);
    }

    /**
     * Returns a string which uniquely represents the group the given model should be in for aggregation.
     * @param Model $model
     * @return string
     */
    private function getGroupKeyForModel(Model $model)
    {
        $key = "";

        foreach ($this->groups as $group) {
            $key .= $model[$group] . "|";
        }

        return $key;
    }

    /**
     * Takes a collection of aggregate columns and calculates their value.
     *
     * @param Aggregate[] $aggregates
     */
    private function processAggregates($aggregates)
    {
        // Step 1. Calculate the aggregate group values.
        foreach ($this->collectionCursor as $model) {
            foreach ($aggregates as $aggregate) {
                $aggregate->calculateByIteration($model, $this->getGroupKeyForModel($model));
            }
        }

        $additionalData = [];

        // Step 2. Extract the group values and apply to models using augmentation data.
        foreach ($aggregates as $aggregate) {
            $groups = $aggregate->getGroups();

            foreach ($this->collectionCursor as $model) {

                $id = $model->UniqueIdentifier;
                $groupKey = $this->getGroupKeyForModel($model);

                if (!isset($additionalData[$id])) {
                    $additionalData[$id] = [];
                }

                if (isset($groups[$groupKey])) {
                    $additionalData[$id][$aggregate->getAlias()] = $groups[$groupKey];
                }
            }
        }

        // Give the cursor the augmentation data.
        $this->collectionCursor->setAugmentationData($additionalData);
    }

    /**
     * Filters the collection to the given identifier.
     *
     * @param $identifier
     * @return mixed
     */
    public function findModelByUniqueIdentifier($identifier)
    {
        $this->filter(new Equals($this->getModelSchema()->uniqueIdentifierColumnName, $identifier));

        return $this[0];
    }

    /**
     * Filter the collection against another using the intersection settings in $intersection.
     *
     * @param CollectionJoin $collectionJoin
     */
    private function filterCollectionJoin(CollectionJoin $collectionJoin)
    {
        $childByIntersectColumn = [];

        // First scan through the collection and make the models easily addressable in an array indexed by the
        // intersection column name
        foreach ($collectionJoin->collection as $childModel) {

            $index = $childModel[$collectionJoin->targetColumnName];

            if (isset($childByIntersectColumn[$index])) {
                if (!is_array($childByIntersectColumn[$index])) {
                    $childByIntersectColumn[$index] = [$childByIntersectColumn[$index]];
                }

                $childByIntersectColumn[$index][] = $childModel;
            } else {
                $childByIntersectColumn[$index] = $childModel;
            }
        }

        $uniqueIdsToFilter = [];
        $augmentationData = [];
        $hasColumnsToPullUp = count($collectionJoin->columnsToPullUp);

        $alreadyDone = [];

        // Next scan through the nested collection and check to see if we need to double up rows in the parent
        // collection. Also pulls in any aggregate data from the nested collection.
        foreach ($this->collectionCursor as $parentModel) {

            $parentId = rtrim($parentModel->uniqueIdentifier, "_");

            if (isset($alreadyDone[$parentId])) {
                continue;
            }

            $alreadyDone[$parentId] = true;

            $parentValue = $parentModel[$collectionJoin->sourceColumnName];
            if (isset($childByIntersectColumn[$parentValue])) {
                $childRows = $childByIntersectColumn[$parentValue];
                $childRows = (is_array($childRows)) ? $childRows : [$childRows];

                $firstRow = true;
                foreach ($childRows as $childRow) {

                    $augmentationIndex = $parentModel->uniqueIdentifier;

                    if (!$firstRow) {
                        // Sometimes if the collection isn't grouped it might actually require us to
                        // support duplicate rows in the outer collection. Later we flatten out the dupes
                        // but we need them now to make sure that aggregates and pull ups work as
                        // expected.
                        $augmentationIndex = $this->collectionCursor->duplicateRow($augmentationIndex);
                    }

                    $firstRow = false;

                    if ($hasColumnsToPullUp) {
                        $augmentationData[$augmentationIndex] = [];

                        foreach ($collectionJoin->columnsToPullUp as $column => $alias) {
                            if (is_numeric($column)) {
                                $column = $alias;
                            }
                            $augmentationData[$augmentationIndex][$alias] = $childRow[$column];
                        }
                    }
                }
            }
        }

        // Finally, if we are intersecting, consider each of our records and see if the intersection
        // constraint is met. Otherwise filter out the row.
        if ($collectionJoin->joinType == CollectionJoin::JOIN_TYPE_INTERSECTION) {
            foreach ($this->collectionCursor as $parentModel) {
                $parentValue = $parentModel[$collectionJoin->sourceColumnName];
                if (!isset($childByIntersectColumn[$parentValue])) {
                    $uniqueIdsToFilter[] = $parentModel->uniqueIdentifier;
                }
            }
        }

        if (count($augmentationData)) {
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

        if ($filter) {

            $uniqueIdentifiersToFilter = [];

            foreach ($this->collectionCursor as $model) {
                if ($filter->shouldFilter($model)) {
                    $uniqueIdentifiersToFilter[] = $model->uniqueIdentifier;
                }
            }

            $this->collectionCursor->filterModelsByIdentifier($uniqueIdentifiersToFilter);
        }
    }

    /**
     * Removes results which should be collapsed into a single grouped result
     */
    private function reduceCursorForGroups()
    {
        $reduceForGroups = $groupKeys = [];
        foreach ($this->collectionCursor as $model) {
            $groupKey = $this->getGroupKeyForModel($model);
            if ($groupKey != '') {
                if (!in_array($groupKey, $groupKeys)) {
                    $groupKeys[] = $groupKey;
                } else {
                    $reduceForGroups[] = $model->getUniqueIdentifier();
                }
            }
        }
        if (count($reduceForGroups) > 0) {
            $this->collectionCursor->filterModelsByIdentifier($reduceForGroups);
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

    /**
     * Deletes all models in the collection by iteration.
     */
    public function deleteAll()
    {
        foreach ($this as $model) {
            $model->delete();
        }
    }

    /**
     * Converts the collection to a simple array by iteration.
     * @return array
     */
    public function toArray()
    {
        $array = [];

        foreach ($this as $model) {
            $array[] = $model;
        }

        return $array;
    }
}
