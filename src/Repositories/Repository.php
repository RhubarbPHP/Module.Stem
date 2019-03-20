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

namespace Rhubarb\Stem\Repositories;

use Gcd\UseCases\Entity;
use Rhubarb\Stem\Aggregates\Aggregate;
use Rhubarb\Stem\Collections\RangeLimitedCursor;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Collections\UniqueIdentifierListCursor;
use Rhubarb\Stem\Exceptions\ModelException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Exceptions\SortNotValidException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\FloatColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * The base class for data repositories.
 *
 * A repository acts as an intermediary between data objects and the data providers. It thus
 * allows for caching and other data manipulations to occur either as part of the
 * default implementation or as a dependency injection.
 *
 * @see \Rhubarb\Stem\Repositories\Offline\Offline
 * @see \Rhubarb\Stem\Repositories\MySql\MySql
 */
abstract class Repository
{
    /**
     * The collection of cached object data.
     *
     * @var array
     */
    public $cachedObjectData = [];

    /**
     * Stores the class name for the default repository used by dataobjects.
     *
     * Change this by calling setDefaultRepositoryClassName()
     *
     * @see Repository::setDefaultRepositoryClassName();
     * @var string
     */
    private static $defaultRepositoryClassName = Offline::class;

    public abstract function store(Model $model);

    /**
     * @param $id
     * @return static|Model
     */
    public abstract function fetchByIdentifier($id): Model;

    public function __construct()
    {
        /*
        $this->modelClassName = get_class($model);
        $this->modelSchema = $model->generateSchema();
        $this->reposSchema = $this->getRepositorySpecificSchema($this->modelSchema);

        $columns = $this->reposSchema->getColumns();

        foreach ($columns as $column) {

            $this->columnTransforms[$column->columnName] =
                [
                    $column->getTransformFromRepository(),
                    $column->getTransformIntoRepository()
                ];

            $storageColumns = $column->createStorageColumns();

            foreach ($storageColumns as $storageColumn) {
                if (!isset($this->columnTransforms[$storageColumn->columnName])) {
                    $this->columnTransforms[$storageColumn->columnName] = [null, null];
                }

                $this->columnTransforms[$storageColumn->columnName][0] =
                    ($this->columnTransforms[$storageColumn->columnName][0] == null) ?
                        $storageColumn->getTransformFromRepository() : $this->columnTransforms[$storageColumn->columnName][0];

                $this->columnTransforms[$storageColumn->columnName][1] =
                    ($this->columnTransforms[$storageColumn->columnName][1] == null) ?
                        $storageColumn->getTransformIntoRepository() : $this->columnTransforms[$storageColumn->columnName][1];
            }
        }
        */
    }

    /**
     * Returns the a new Aggregate object specific to this repository for the passed generic aggregate.
     * @param Aggregate $aggregate
     * @return bool|Aggregate
     */
    public function getRepositorySpecificAggregate(Aggregate $aggregate)
    {
        return false;
    }

    /**
     * Returns the a new Filter object specific to this repository for the passed generic filter.
     * @param Filter $filter
     * @return bool|Filter
     */
    public function getRepositorySpecificFilter(Filter $filter)
    {
        return false;
    }

    /**
     * Checks if raw repository data needs transformed before passing to the model.
     *
     * @param  $modelData
     * @return mixed
     */
    public function transformDataFromRepository($modelData)
    {
        foreach ($this->columnTransforms as $columnName => $transforms) {
            if ($transforms[0] !== null) {
                $closure = $transforms[0];

                $modelData[$columnName] = $closure($modelData);
            }
        }

        return $modelData;
    }

    /**
     * Checks if model data needs transformed into raw model data before passing it for storage.
     *
     * @param  $modelData array  An array of model data to transform.
     * @return mixed            The transformed data
     */
    protected function transformDataForRepository($modelData)
    {
        foreach ($this->columnTransforms as $columnName => $transforms) {
            if ($transforms[1] !== null) {
                $closure = $transforms[1];

                $transformedData = $closure($modelData);

                if (is_array($transformedData)) {
                    // If the original value is to be retained, the transform function should
                    // explicitly return it - in other cases we need to unset it here.
                    unset($modelData[$columnName]);

                    $modelData = array_merge($modelData, $transformedData);
                } else {
                    $modelData[$columnName] = $transformedData;
                }
            }
        }

        return $modelData;
    }

    /**
     * The repository to use when creating the repo specific schema. This is useful if you have a custom repo which
     * extends an existing repo and inherits it's columns and filters
     *
     * @return string
     */
    protected function getModelSchemaRepoClassName()
    {
        return static::class;
    }

    private function getRepositorySpecificSchema(ModelSchema $genericSchema)
    {
        $schemaRepoClassName = $this->getModelSchemaRepoClassName();
        $reposName = basename(str_replace("\\", "/", $schemaRepoClassName));

        // Get the provider specific implementation of the column.
        $className = "\\" . str_replace("/", "\\", dirname(str_replace("\\", "/", $schemaRepoClassName))) . "\\Schema\\" . $reposName . "ModelSchema";

        $superType = $genericSchema;

        if (class_exists($className)) {
            $superType = call_user_func_array(
                $className . "::fromGenericSchema",
                [$genericSchema]
            );

            // getRepositorySpecificSchema could return false if it doesn't supply any schema details.
            if ($superType === false) {
                $superType = $genericSchema;
            }
        }

        return $superType;
    }

    public function getModelClass()
    {
        return $this->modelClassName;
    }

    /**
     * Gets the schema object for this repository.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    public function getRepositorySchema()
    {
        return $this->reposSchema;
    }

    /**
     * Gets the schema object for the underlying model.
     *
     * Differs from getRepositorySchema in that this will be the original column types,
     * not the repository specific column types.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    public function getModelSchema()
    {
        return $this->modelSchema;
    }

    /**
     * Commits changes to the repository in batch against a collection.
     *
     * @param RepositoryCollection $collection
     * @param $propertyPairs
     */
    public function batchCommitUpdatesFromCollection(RepositoryCollection $collection, $propertyPairs)
    {
        foreach ($collection as $item) {
            $item->mergeRawData($propertyPairs);
            $item->save();
        }
    }

    /**
     * Computes the given aggregates and returns an array of answers
     *
     * An answer will be null if the repository is unable to answer it.
     *
     * @param  Aggregate[] $aggregates
     * @param  RepositoryCollection $collection
     * @return array
     */
    public function calculateAggregates($aggregates, RepositoryCollection $collection)
    {
        return [];
    }

    public function canFilterExclusivelyByRepository(RepositoryCollection $collection)
    {
        return false;
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
        $ids = array_keys($this->cachedObjectData);

        return new UniqueIdentifierListCursor(array_values($ids), $this->modelClassName);
    }

    /**
     * Clear's the repository of all it's cached data.
     */
    public function clearObjectCache()
    {
        $this->cachedObjectData = [];
    }

    abstract public function clearRepositoryData();

    /**
     * Returns a new default repository of the current default repository type.
     *
     * @see    Repository::setDefaultRepositoryClassName()
     * @param  \Rhubarb\Stem\Models\Model $forModel
     * @return mixed
     */
    public static function getNewDefaultRepository(Model $forModel)
    {
        $defaultRepository = self::$defaultRepositoryClassName;

        return new $defaultRepository($forModel);
    }

    /**
     * Takes the model data from the data object and stores it in the repository cache.
     *
     * @param \Rhubarb\Stem\Models\Model $object
     */
    final protected function cacheObjectData(Model $object)
    {
        $uniqueIdentifier = $object->UniqueIdentifier;

        if ($uniqueIdentifier === null) {
            return;
        }

        if (!isset($this->cachedObjectData[$uniqueIdentifier])) {
            $this->cachedObjectData[$uniqueIdentifier] = $object->exportRawData();
        } else {
            $this->cachedObjectData[$uniqueIdentifier] = array_merge(
                $this->cachedObjectData[$uniqueIdentifier],
                $object->exportRawData()
            );
        }
    }

    /**
     * Removes the model data of the data object from the repository cache.
     *
     * @param \Rhubarb\Stem\Models\Model $object
     */
    final protected function deleteObjectFromCache(Model $object)
    {
        $uniqueIdentifier = $object->UniqueIdentifier;

        if ($uniqueIdentifier === null) {
            return;
        }

        if (isset($this->cachedObjectData[$uniqueIdentifier])) {
            unset($this->cachedObjectData[$uniqueIdentifier]);
        }
    }

    /**
     * Returns the model data for a given object.
     *
     * Uses cached data if it exists and if not will request that the object is hydrated.
     *
     * @see    Repository::fetchMissingObjectData()
     * @param  \Rhubarb\Stem\Models\Model $object
     * @param  $uniqueIdentifier
     * @param  array $relationshipsToAutoHydrate An array of relationship names which should be automatically hydrated (i.e. joined) during the hydration of this object. Not supported by all Repositories.
     *                                            (i.e. joined) during the hydration of this object. Not supported by all
     *                                            Repositories.
     * @return mixed
     */
    final protected function fetchObjectData(Model $object, $uniqueIdentifier, $relationshipsToAutoHydrate = [])
    {
        if (!isset($this->cachedObjectData[$uniqueIdentifier])) {
            $this->cachedObjectData[$uniqueIdentifier] = $this->fetchMissingObjectData(
                $object,
                $uniqueIdentifier,
                $relationshipsToAutoHydrate
            );
        }

        return $this->cachedObjectData[$uniqueIdentifier];
    }

    /**
     * Fetches new data for a given object.
     *
     * This function should be overriden by Repository implementations to fetch the data
     * from it's back end data store.
     *
     * @param  \Rhubarb\Stem\Models\Model $object
     * @param  $uniqueIdentifier
     * @param  array $relationshipsToAutoHydrate An array of relationship names which should be automatically hydrated (i.e. joined) during the hydration of this object. Not supported by all Repositories.
     *                                            (i.e. joined) during the hydration of this object. Not supported by all
     *                                            Repositories.
     * @return array
     * @throws RecordNotFoundException
     */
    protected function fetchMissingObjectData(Model $object, $uniqueIdentifier, $relationshipsToAutoHydrate = [])
    {
        throw new RecordNotFoundException(get_class($object), $uniqueIdentifier);
    }

    /**
     * Fetches the data for an object and populates it's model data with the same.
     *
     * @param \Rhubarb\Stem\Models\Model $object
     * @param $uniqueIdentifier
     * @param array $relationshipsToAutoHydrate An array of relationship names which should be automatically hydrated (i.e. joined) during the hydration of this object. Not supported by all Repositories.
     *                                            (i.e. joined) during the hydration of this object. Not supported by all
     *                                            Repositories.
     */
    final public function hydrateObject(Model $object, $uniqueIdentifier, $relationshipsToAutoHydrate = [])
    {
        $objectData = $this->fetchObjectData($object, $uniqueIdentifier, $relationshipsToAutoHydrate);

        $object->importRawData($objectData);
    }

    /**
     * Rehydrates the model fresh from the back end data store.
     *
     * @param Model $object
     * @param $uniqueIdentifier
     */
    public function reHydrateObject(Model $object, $uniqueIdentifier)
    {
        $this->hydrateObject($object, $uniqueIdentifier);
    }

    /**
     * save the data object.
     *
     * In the base implementation the object is only cached, however this function also
     * calls onObjectSaved() to allow extenders to store the object permanently in a
     * back end data store.
     *
     * @see    Repository::onObjectSaved()
     * @throws ModelException When the object has no unique identifier.
     * @param  \Rhubarb\Stem\Models\Model $object
     */
    final public function saveObject(Model $object)
    {
        $this->onObjectSaved($object);

        if ($object->getUniqueIdentifier() === null) {
            throw new ModelException("The object could not be saved as it has no unique identifier.", $object);
        }

        $this->cacheObjectData($object);
    }

    public abstract function getEntityByIdentifier($id): Entity;

    public abstract function storeEntity(Entity $entity);

    public abstract function deleteEntity(Entity $entity);
}
