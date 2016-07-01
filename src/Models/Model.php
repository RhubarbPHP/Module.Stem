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

namespace Rhubarb\Stem\Models;

require_once __DIR__ . '/../Schema/SolutionSchema.php';
require_once __DIR__ . '/../Schema/ModelSchema.php';

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Modelling\ModelState;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Decorators\DataDecorator;
use Rhubarb\Stem\Exceptions\DeleteModelException;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\ModelValueInitialiserInterface;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * The standard active record implementation in the Core
 *
 * This class serves as a base class for model objects
 *
 * @property string UniqueIdentifierColumnName
 * @property string UniqueIdentifier
 */
abstract class Model extends ModelState
{
    private $eventsToRaiseAfterSave = [];

    /** @var callable[] */
    private $callbacksToRunAfterSave = [];

    final public function __construct($uniqueIdentifier = null)
    {
        $this->modelName = SolutionSchema::getModelNameFromClass(get_class($this));

        if ($this->modelName === null) {
            // If our model hasn't been registered with the SolutionSchema - we can still use it
            // However we will assume that the model name would have been just the last part of the
            // full class name.
            $this->modelName = basename(str_replace("\\", "/", get_class($this)));
        }

        $schema = $this->getSchema();
        $this->uniqueIdentifierColumnName = $schema->uniqueIdentifierColumnName;

        if (!isset(self::$modelDataTransforms[$this->modelName])) {
            // Ensure we have our model transforms in place.

            self::$modelDataTransforms[$this->modelName] = [];

            $schema = $this->getSchema();
            $columns = $schema->getColumns();

            foreach ($columns as $column) {
                self::$modelDataTransforms[$this->modelName][$column->columnName] =
                    [
                        $column->getTransformFromModelData(),
                        $column->getTransformIntoModelData()
                    ];
            }
        }

        $this->uniqueIdentifier = $uniqueIdentifier;

        if ($uniqueIdentifier !== null) {
            $this->loadFromUniqueIdentifier($uniqueIdentifier);
        } else {
            $this->onNewModelInitialised();
        }

        parent::__construct();
    }

    public function __clone()
    {
        $this->setUniqueIdentifier(null);
        $this->onNewModelInitialised();
    }

    /**
     * Gets the most appropriate decorator for this model.
     *
     * @return DataDecorator
     */
    public function getDecorator()
    {
        return DataDecorator::getDecoratorForModel($this);
    }

    protected function setDefaultValues()
    {

    }

    public function importRawData($data)
    {
        parent::importRawData($data);

        $this->uniqueIdentifier = $this[$this->uniqueIdentifierColumnName];
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Override this to perform actions when a record is loaded.
     */
    protected function onLoaded()
    {

    }

    /**
     * Requests that the model data be re-fetched from the repository
     */
    public function reload()
    {
        $this->propertyCache = [];

        $repository = $this->getRepository();
        $repository->reHydrateObject($this, $this->uniqueIdentifier);
    }

    /**
     * Call this function to raise an event.
     *
     * In addition to $event you can pass any number of other events which are passed through
     * to the event handling delegate.
     *
     * @param string $event The name of the event
     * @return mixed|null
     */
    protected function raiseEvent($event)
    {
        $args = func_get_args();

        array_splice($args, 1, 0, [$this]);

        // Raise a class level dispatch which allows global listeners like the solution schema to
        // co-ordinate inter model activities.

        call_user_func_array('Rhubarb\Stem\Models\ModelEventManager::dispatchModelEvent', $args);
    }

    /**
     * Call this function to raise an event after the model is saved.
     *
     * In addition to $event you can pass any number of other events which are passed through
     * to the event handling delegate.
     *
     * @deprecated Use performAfterSave to run a callback directly instead
     * @see performAfterSave()
     *
     * @param string $event The name of the event
     * @return mixed|null
     */
    protected function raiseEventAfterSave($event)
    {
        $args = func_get_args();
        $this->eventsToRaiseAfterSave[] = $args;
    }

    /**
     * Allows a callback function to be run when the model is saved.
     *
     * @param callable $callback Callback to run when the model is saved
     * @param null|string $key Set a key to be able to override this callback with another to prevent multiple callbacks for the same purpose
     */
    protected function performAfterSave(callable $callback, $key = null)
    {
        if ($key) {
            $this->callbacksToRunAfterSave[$key] = $callback;
        } else {
            $this->callbacksToRunAfterSave[] = $callback;
        }
    }

    /**
     */
    public function ensureRelationshipsArePopulated()
    {
        $className = get_class($this);
        $relationships = &Application::current()->getSharedArray("ModelRelationships");

        if (!isset($relationships[$this->modelName])) {
            $relationships[$this->modelName] = SolutionSchema::getAllRelationshipsForModel($this->modelName);

            return $className;
        }
    }

    protected function getPublicPropertyList()
    {
        $properties = [$this->uniqueIdentifierColumnName];

        $schema = $this->getSchema();

        if ($schema->labelColumnName != "") {
            $properties[] = $schema->labelColumnName;
        }

        return $properties;
    }

    /**
     * For performance the unique identifier is captured here.
     *
     * @var null
     */
    protected $uniqueIdentifier = null;

    /**
     * For performance the unique identifier column name is captured here.
     *
     * @var null
     */
    protected $uniqueIdentifierColumnName = null;

    /**
     * Stores the name of the model.
     *
     * Should only be set in the constructor of a model.
     *
     * @var string
     */
    protected $modelName = "";

    /**
     * A cached collection of any properties that require it.
     *
     * @var array
     */
    private $propertyCache = [];

    private static $modelDataTransforms = [];

    /**
     * Contains an array of relationship names that should be automatically hydrated when loading this model.
     *
     * Designed to be set by the child class in it's constructor.
     *
     * Auto hydrating relationships allows for faster sorting and filtering on collections where the sort or
     * filter is on a related model column. However it does result in larger data sets being stored in memory
     * so it is worth performing bench marks to be sure the auto hydration is in fact having a positive rather
     * than negative impact.
     *
     * NOT YET IMPLEMENTED
     *
     * @var array
     */
    protected $autoHydratedRelationships = [];

    /**
     * Returns the cached repository and generates one if it doesn't exist.
     *
     * @see DataObject::createRepository()
     * @return \Rhubarb\Stem\Repositories\Repository
     */
    final public function getRepository()
    {
        $repositories = &Application::current()->getSharedArray("ModelRepositories");
        if (!isset($repositories[$this->modelName])) {
            $repositories[$this->modelName] = $this->createRepository();
        }

        return $repositories[$this->modelName];
    }

    /**
     * Removes all repositories from all models.
     *
     * Generally only used in unit testing to allow the default repository to be changed.
     *
     * @deprecated Instantiate a new application instead.
     */
    public static function deleteRepositories()
    {
        self::clearAllRepositories();
    }

    /**
     * Creates the repository appropriate for this object type.
     *
     * To be overridden when necessary.
     *
     * @throws \Rhubarb\Stem\Exceptions\ModelException
     * @return \Rhubarb\Stem\Repositories\Repository
     */
    protected function createRepository()
    {
        return Repository::getNewDefaultRepository($this);
    }

    /**
     * Removes the cache of objects from the repository representing this model type.
     */
    public static function clearObjectCache()
    {
        $object = SolutionSchema::getModel(get_called_class());
        $repository = $object->getRepository();
        $repository->clearObjectCache();
    }

    /**
     * Blitzes the repositories.
     *
     * After this call all cached data for model objects will be lost and repositories will be recreated when
     * next required.
     */
    public static function clearAllRepositories()
    {
        Application::current()->clearSharedArray("ModelRepositories");
        Application::current()->clearSharedArray("ModelRelationships");
    }

    public function clearPropertyCache()
    {
        $this->propertyCache = [];
    }

    /**
     * Finds the first model matching the given filters.
     *
     * @param Filter $filter
     * @throws RecordNotFoundException
     * @return Model|static
     */
    public static function findFirst(Filter $filter = null)
    {
        $results = static::find($filter);

        if (sizeof($results) == 0) {
            throw new RecordNotFoundException(get_called_class(), 0);
        }

        return $results[0];
    }

    private function onNewModelInitialised()
    {
        $this->newRecord = true;
        $schema = $this->getSchema();
        $columns = $schema->getColumns();

        foreach ($columns as $column) {
            if ($column instanceof ModelValueInitialiserInterface) {
                $column->onNewModelInitialising($this);
            }
        }

        $this->setDefaultValues();
    }

    /**
     * Finds the last model matching the given filters.
     *
     * @param Filter $filter
     * @throws RecordNotFoundException
     * @return static
     */
    public static function findLast(Filter $filter = null)
    {
        $results = static::find($filter);
        $modelClass = get_called_class();
        /** @var Model $model */
        $model = new $modelClass();
        $results->addSort($model->getUniqueIdentifierColumnName(), false);

        if (sizeof($results) == 0) {
            throw new RecordNotFoundException(get_called_class(), 0);
        }

        return $results[0];
    }

    /**
     * Returns the Collection of models matching the given filter.
     *
     * @param Filter $filter
     * @return Collection|static[]
     */
    public static function find(Filter ...$filters)
    {
        $modelClass = get_called_class();
        $collection = new Collection($modelClass);
        $collection->filter(...$filters);
        return $collection;
    }

    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    abstract protected function createSchema();

    protected function extendSchema(ModelSchema $schema)
    {

    }

    final public function generateSchema()
    {
        $schema = $this->createSchema();
        $this->extendSchema($schema);

        return $schema;
    }

    /**
     * Returns a cached instance of the model schema.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    final public function getSchema()
    {
        return $this->getRepository()->getModelSchema();
    }

    /**
     * Get's the label for this model object.
     *
     * Uses the labelColumnName provided by the model schema.
     */
    public function getLabel()
    {
        $schema = $this->getSchema();

        if ($schema->labelColumnName == "") {
            return "";
        }

        return $this[$schema->labelColumnName];
    }

    /**
     * Gets the name of the label column.
     *
     * @return string
     */
    public function getLabelColumnName()
    {
        $schema = $this->getSchema();

        return $schema->labelColumnName;
    }

    /**
     * Persists the model data associated with this data object with the relevant repository.
     *
     * Calls beforeSave() and afterSave() as appropriate.
     *
     * @param bool $forceSaveRegardlessOfState If set, the repository save will be forced even if hasChanged() returns false
     * @return mixed
     * @throws ModelConsistencyValidationException
     * @throws \Rhubarb\Stem\Exceptions\ModelException
     */
    final public function save($forceSaveRegardlessOfState = false)
    {
        try {
            $this->isConsistent();
        } catch (ModelConsistencyValidationException $er) {
            // If the model isn't consistent we don't let it get into the database.
            throw $er;
        }

        if (!$forceSaveRegardlessOfState && !$this->isNewRecord() && !$this->hasChanged()) {
            return $this->uniqueIdentifier;
        }

        $this->beforeSave();
        $this->raiseEvent("BeforeSave");

        $repository = $this->getRepository();
        $repository->saveObject($this);

        $this->newRecord = false;

        $this->callAfterSaveCallbacks();

        $this->afterSave();
        $this->raiseEvent("AfterSave");

        $this->takeChangeSnapshot();

        return $this->uniqueIdentifier;
    }

    private function callAfterSaveCallbacks()
    {
        $eventsToRaiseAfterSave = $this->eventsToRaiseAfterSave;
        $this->eventsToRaiseAfterSave = [];

        foreach ($eventsToRaiseAfterSave as $eventArgs) {
            call_user_func_array([$this, "raiseEvent"], $eventArgs);
        }

        $callbacksToRunAfterSave = $this->callbacksToRunAfterSave;
        $this->callbacksToRunAfterSave = [];

        foreach ($callbacksToRunAfterSave as $callback) {
            $callback();
        }
    }

    /**
     * Override this to make changes just before the model is committed to the repository during a save operation
     */
    protected function beforeSave()
    {
    }

    /**
     * Override this to make changes just after the model has been committed to the repository during a save operation
     */
    protected function afterSave()
    {
    }

    public function delete()
    {
        if ($this->isNewRecord()) {
            throw new DeleteModelException("New models can't be deleted.");
        }

        $this->beforeDelete();
        $this->raiseEvent("BeforeDelete");

        $repository = $this->getRepository();
        $repository->deleteObject($this);

        $this->afterDelete();
        $this->raiseEvent("AfterDelete");
    }

    /**
     * Override this to make changes just before the model is deleted from the repository during a delete operation
     */
    protected function beforeDelete()
    {
    }

    /**
     * Override this to make changes just after the model has been deleted from the repository during a delete operation
     */
    protected function afterDelete()
    {
    }

    /**
     * Get's the name of the unique identifier column for this object.
     *
     * This consults the schema to determine this so don't over use this method.
     * Potentially we might cache the result of createSchema() to ensure this isn't a performance
     * burden.
     *
     * @return string
     */
    public function getUniqueIdentifierColumnName()
    {
        return $this->uniqueIdentifierColumnName;
    }

    /**
     * Get's the unique identifier for this record or null if it doesn't have one.
     *
     * @return null
     */
    public function getUniqueIdentifier()
    {
        return $this->uniqueIdentifier;
    }

    /**
     * Returns the name of the model.
     *
     * Normally the alias provided in the solution scheme, unless the model is unregistered in which case
     * it will be the last part of the class name.
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Set's the unique identifier for this object.
     *
     * This method is provided for the repositories to update newly created objects with their
     * unique identifier and should not be used in any other circumstances unless you really know
     * what you're doing!
     *
     * @param $value
     */
    public function setUniqueIdentifier($value)
    {
        $this->newRecord = $value === null;
        $this->uniqueIdentifier = $value;
        $this[$this->uniqueIdentifierColumnName] = $value;
    }

    /**
     * @var bool Flag used to control the result of calls to isNewRecord
     */
    private $newRecord = true;

    /**
     * Returns true if the record is new, using a flag set on the object when it is saved/loaded with an ID
     *
     * @return bool
     */
    public function isNewRecord()
    {
        return $this->newRecord;
    }

    public function __set($propertyName, $value)
    {
        if (isset(self::$modelDataTransforms[$this->modelName][$propertyName][1])) {
            $closure = self::$modelDataTransforms[$this->modelName][$propertyName][1];
            $value = $closure($value);
        }

        if (strpos($propertyName, ".") !== false) {
            $parts = explode(".", $propertyName);

            $firstStep = $this[$parts[0]];

            if ($firstStep === null || !($firstStep instanceof Model)) {
                // If the next item in the chain is not model object we can't ask it to
                // set it's value.
                return;
            }

            $firstStep[$parts[1]] = $value;
        } else {
            parent::__set($propertyName, $value);

            if (isset($this->propertyCache[$propertyName])) {
                $this->propertyCache[$propertyName] = $propertyName;
            }
        }

        if ($propertyName == $this->uniqueIdentifierColumnName) {
            $this->uniqueIdentifier = $value;
        }
    }

    public function __isset($propertyName)
    {
        if (parent::__isset($propertyName)) {
            return true;
        }

        $schema = $this->getSchema();
        $columns = $schema->getColumns();

        // Check for schema columns
        if (isset($columns[$propertyName])) {
            return true;
        }

        // Check for dot operator
        if (strpos($propertyName, ".") !== false) {
            $parts = explode(".", $propertyName);

            $firstStep = $this[$parts[0]];

            if ($firstStep === null || !($firstStep instanceof Model)) {
                // If the next item in the chain is not model object we can't ask it to
                // set it's value.
                return false;
            }

            if (isset($firstStep[$parts[1]])) {
                return true;
            }
        }

        // Check for relationships
        $className = get_class($this);

        $relationships = &Application::current()->getSharedArray("ModelRelationships");

        if (!isset($relationships[$className])) {
            $relationships[$className] = SolutionSchema::getAllRelationshipsForModel($this->modelName);
        }

        if (isset($relationships[$className][$propertyName])) {
            return true;
        }

        return false;
    }

    public function __get($propertyName)
    {
        // Should any type of magical getter below require that the value is cached for performance
        // this boolean will be set to true. At the end of the function we pick up on this and do the
        // caching - instead of having the caching line repeated all over the place.
        $addToPropertyCache = false;

        $value = parent::__get($propertyName);

        if ($value === null) {
            if (isset($this->propertyCache[$propertyName])) {
                return $this->propertyCache[$propertyName];
            }

            // Handle the dot operator by passing control to the next object in the chain.
            if (strpos($propertyName, ".") !== false) {
                $parts = explode(".", $propertyName, 2);

                $firstStep = $this[$parts[0]];

                if ($firstStep === null || !($firstStep instanceof Model)) {
                    // If the next item in the chain is not model object we can't ask it to return
                    // a value can we!
                    return null;
                }

                return $firstStep[$parts[1]];
            }

            $this->ensureRelationshipsArePopulated();
            $relationships = &Application::current()->getSharedArray("ModelRelationships");

            if (isset($relationships[$this->modelName][$propertyName])) {
                $relationship = $relationships[$this->modelName][$propertyName];
                $value = $relationship->fetchFor($this);

                if ($value instanceof Model) {
                    $addToPropertyCache = true;
                }
            }

            if ($propertyName != $this->UniqueIdentifierColumnName) {
                // See if the column has a default
                $columns = $this->getSchema()->getColumns();
                if (isset($columns[$propertyName])) {
                    $defaultValue = $columns[$propertyName]->getDefaultValue();
                    if (is_object($defaultValue)) {
                        $defaultValue = clone $defaultValue;
                    }
                    // We actually have to set the default value on the model - to ensure this value
                    // goes through the normal setting pathway.
                    // First we disable change events - this is to stop infinite loops and really would
                    // you expect those to fire for the setting of defaults anyway?
                    $this->propertyChangeEventsDisabled = true;
                    if ($defaultValue !== null) {
                        $this[$propertyName] = $defaultValue;
                    }
                    $this->propertyChangeEventsDisabled = false;

                    $value = parent::__get($propertyName);
                }
            }
        }

        if ($addToPropertyCache) {
            $this->propertyCache[$propertyName] = $value;
        }

        if (isset(self::$modelDataTransforms[$this->modelName][$propertyName][0])) {
            $closure = self::$modelDataTransforms[$this->modelName][$propertyName][0];
            $value = $closure($value);
        }

        return $value;
    }

    /**
     * Returns the schema Column object for the matching column reference.
     *
     * A column reference might be a column name or a Relationship.ColumnName expressions.
     *
     * @param string $columnReference
     * @param bool $fromModelSchema True to use model schema, false to use repository schema
     * @return null|\Rhubarb\Stem\Schema\Columns\Column
     */
    private function getColumnSchemaForColumnReference($columnReference, $fromModelSchema)
    {
        if (strpos($columnReference, ".") !== false) {
            $parts = explode(".", $columnReference);

            $relationshipName = $parts[0];

            $this->ensureRelationshipsArePopulated();
            $relationships = &Application::current()->getSharedArray("ModelRelationships");

            if (isset($relationships[$this->modelName][$relationshipName])) {
                $relationship = $relationships[$this->modelName][$relationshipName];
                $relatedModel = SolutionSchema::getModel($relationship->getTargetModelName());

                return $relatedModel->getColumnSchemaForColumnReference($parts[1], $fromModelSchema);
            }
        }

        $schema = $fromModelSchema ? $this->getSchema() : $this->getRepository()->getRepositorySchema();
        $columns = $schema->getColumns();

        if (isset($columns[$columnReference])) {
            return $columns[$columnReference];
        }

        return null;
    }

    /**
     * Returns the generic repository schema Column object for the matching column reference.
     *
     * A column reference might be a column name or a Relationship.ColumnName expressions.
     *
     * @param string $columnReference
     * @return null|\Rhubarb\Stem\Schema\Columns\Column
     */
    public function getModelColumnSchemaForColumnReference($columnReference)
    {
        return $this->getColumnSchemaForColumnReference($columnReference, true);
    }

    /**
     * Returns the repository specific schema Column object for the matching column reference.
     *
     * A column reference might be a column name or a Relationship.ColumnName expressions.
     *
     * @param string $columnReference
     * @return null|\Rhubarb\Stem\Schema\Columns\Column
     */
    public function getRepositoryColumnSchemaForColumnReference($columnReference)
    {
        return $this->getColumnSchemaForColumnReference($columnReference, false);
    }

    /**
     * Override to apply validation rules.
     *
     * Return a dictionary of key value pairs, the value being the validation message
     * and the key being a reference to the test undertaken (normally the column being
     * tested)
     *
     * Return an empty array if all is well.
     *
     * @return string[]
     */
    protected function getConsistencyValidationErrors()
    {
        return [];
    }

    /**
     * Returns true of the data in the model is consistent with the entity we are trying to model.
     *
     * To implement your validation rules overide the getConsistencyValidationErrors()
     *
     * @param bool $throwException Set to false to stop an exception being thrown if the models is inconsistent
     * @throws \Exception
     * @throws \Rhubarb\Stem\Exceptions\ModelConsistencyValidationException
     * @return bool
     */
    final public function isConsistent($throwException = true)
    {
        $errors = $this->getConsistencyValidationErrors();

        if (sizeof($errors) == 0) {
            return true;
        }

        if ($throwException) {
            throw new ModelConsistencyValidationException($errors);
        }

        return false;
    }

    /**
     * Override this method to check for/create specific initial records
     *
     * @param int $oldVersion Version number upgrading from
     * @param int $newVersion Version number upgrading to
     */
    public static function checkRecords($oldVersion, $newVersion)
    {
    }

    /**
     * Loads a record using the unique identifier.
     *
     * @param $uniqueIdentifier
     */
    protected function loadFromUniqueIdentifier($uniqueIdentifier)
    {
        $this->newRecord = false;
        $repository = $this->getRepository();
        $repository->hydrateObject($this, $uniqueIdentifier);
        $this->onLoaded();
    }
}
