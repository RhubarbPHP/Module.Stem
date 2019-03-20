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

namespace Rhubarb\Stem\Schema;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Stem\Exceptions\RelationshipDefinitionException;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\ManyToMany;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\Relationships\Relationship;

/**
 * Encapsulates an entire solution schema including it's model objects and the relationships between them.
 *
 * Note that the design of this class is not to store model objects or their schema objects, but rather only the
 * names of the classes involved. This is to ensure that in a big project like Greenbox with 200+ model classes there
 * is not a massive performance penalty for each request.
 */
abstract class SolutionSchema
{
    /**
     * An array of the registered schema classes
     *
     * @var array
     */
    private static $solutionSchemaClasses = [];

    /**
     * An array of the initialised schema objects
     *
     * @var array
     */
    private static $solutionSchemas = [];

    /**
     * A mapping of model names to model classes
     *
     * @var array
     */
    protected $modelSchemaAliases = [];

    /**
     * A list of instantiated model schemas by alias name
     *
     * @var array
     */
    protected $modelSchemas = [];

    /**
     * A mapping of model classes to model names
     *
     * @var array
     */
    private $modelSchemaClassNames = [];

    /**
     * A collection of relationships defined by this schema
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * A cache array of relationships
     *
     * @var array
     */
    private static $relationshipCache = [];

    /**
     * @var Repository
     */
    private $repository;


    /**
     * SolutionSchema constructor.
     *
     * @param int $version
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Registers a schema class to provide the schema for a given schema name.
     *
     * Not that the class should just be a class name, not an instance of the class. This ensures that schema objects
     * (which can be quite large) are only instantiated when needed.
     *
     * @param $schemaName
     * @param $schemaClass
     */
    public static function registerSchema($schemaName, $schemaClass)
    {
        self::$solutionSchemaClasses[$schemaName] = $schemaClass;

        // Invalidate the caches
        self::$schemaClassesCache = null;
        self::$schemaNamesCache = null;
        self::$relationshipCache = null;
    }

    /**
     * Un-registers all solution schemas.
     *
     * Only really used in unit testing.
     */
    public static function clearSchemas()
    {
        self::$solutionSchemaClasses = [];
        self::$solutionSchemas = [];
    }

    /**
     * The correct place for implementers to define relationships.
     */
    protected function defineRelationships()
    {

    }

    /**
     * Gets an array of all named models and their class names
     *
     * @return Model[]
     */
    public function getAllModelSchemas()
    {
        return $this->modelSchemaAliases;
    }

    /**
     * Gets an empty model of the appropriate type for a given model name.
     *
     * @param $modelName
     * @param null $uniqueIdentifier Optionally a unique identifier to load.
     *
     * @return Model
     */
    public static function getModel($modelName, $uniqueIdentifier = null)
    {
        $class = self::getSchemaClass($modelName);
        $model = new $class($uniqueIdentifier);

        return $model;
    }

    /**
     * Get's the schema for a particular model by name or class.
     *
     * @param  string $schemaName The name or class name of the model
     * @return ModelSchema
     */
    public function getSchema($schemaName)
    {
        return $this->modelSchemaAliases[$schemaName]->getSchema();
    }

    /**
     * Instantiates (if necessary) and returns an instance of a schema object matched by its name.
     *
     * @param  $solutionSchemaName
     * @throws \Rhubarb\Stem\Exceptions\SchemaNotFoundException
     * @throws \Rhubarb\Stem\Exceptions\SchemaRegistrationException
     * @return SolutionSchema
     */
    public static function getSolutionSchema($solutionSchemaName)
    {
        if (!isset(self::$solutionSchemas[$solutionSchemaName])) {
            if (!isset(self::$solutionSchemaClasses[$solutionSchemaName])) {
                throw new SchemaNotFoundException($solutionSchemaName);
            }

            $schemaClass = self::$solutionSchemaClasses[$solutionSchemaName];
            $schema = new $schemaClass();

            if (!($schema instanceof SolutionSchema)) {
                throw new SchemaRegistrationException($solutionSchemaName, $schemaClass);
            }

            self::$solutionSchemas[$solutionSchemaName] = $schema;

            $schema->defineRelationships();
        }

        return self::$solutionSchemas[$solutionSchemaName];
    }

    /**
     * Returns an array of all schema objects registered.
     *
     * @return SolutionSchema[]
     */
    public static function getAllSchemas()
    {
        foreach (self::$solutionSchemaClasses as $schemaName => $schemaClass) {
            self::getSolutionSchema($schemaName);
        }

        return self::$solutionSchemas;
    }

    /**
     * Returns all registered relationships for a given model from all registered schemas.
     *
     * @param  $modelClassName
     * @return Relationship[]
     */
    public static function getAllRelationshipsForModel($modelClassName)
    {
        $modelClassName = self::getSchemaClass($modelClassName);

        if (!isset(self::$relationshipCache[$modelClassName])) {
            $schemas = self::getAllSchemas();
            $relationships = [];

            foreach ($schemas as $schema) {
                if (isset($schema->relationships[$modelClassName])) {
                    $relationships = array_merge($relationships, $schema->relationships[$modelClassName]);
                }
            }

            self::$relationshipCache[$modelClassName] = $relationships;
        }

        return self::$relationshipCache[$modelClassName];
    }

    /**
     * Gets all the one to one relationships for a model in an array keyed by the column name in the source model.
     *
     * @param  $modelClassName
     * @return OneToOne[]
     */
    public static function getAllOneToOneRelationshipsForModelBySourceColumnName($modelClassName)
    {
        $relationships = self::getAllRelationshipsForModel($modelClassName);
        $columnRelationships = [];

        foreach ($relationships as $relationship) {
            if ($relationship instanceof OneToOne) {
                $columnName = $relationship->getSourceColumnName();

                $columnRelationships[$columnName] = $relationship;
            }
        }

        return $columnRelationships;
    }

    private static $schemaClassesCache = null;
    private static $schemaNamesCache = null;

    /**
     * Gets the full class name of a model using it's model name.
     *
     * @param  $schemaName
     * @return null
     */
    public static function getSchemaClass($schemaName)
    {
        // If the name contains a backslash it is already fully qualified. However in some cases
        // a model might be replaced by a new class and so we must first look to see if this model is
        // mapped and if so return it's replacement instead.
        if (stripos($schemaName, "\\") !== false) {
            $newName = self::getSchemaNameFromClass($schemaName);

            if ($newName === null) {
                // $name hasn't been registered as a model object. Play safe and return the
                // same class name passed to us.
                return '\\' . ltrim($schemaName, '\\');
            }

            $schemaName = $newName;
        }

        if (self::$schemaClassesCache == null) {
            self::$schemaClassesCache = [];

            $schemas = self::getAllSchemas();

            self::$schemaNamesCache = [];

            foreach ($schemas as $schema) {
                self::$schemaClassesCache = array_merge(self::$schemaClassesCache, $schema->modelSchemaAliases);
                self::$schemaNamesCache = array_merge(self::$schemaNamesCache, $schema->modelSchemaClassNames);
            }
        }

        if (isset(self::$schemaClassesCache[$schemaName])) {
            return '\\' . ltrim(self::$schemaClassesCache[$schemaName], '\\');
        }

        return null;
    }

    public static function getSchemaNameFromClass($class)
    {
        if (self::$schemaNamesCache == null) {
            self::$schemaNamesCache = [];

            $schemas = self::getAllSchemas();

            foreach ($schemas as $schema) {
                self::$schemaNamesCache = array_merge(self::$schemaNamesCache, $schema->modelSchemaClassNames);
            }
        }

        $classNameWithNoLeadingSlash = ltrim($class, '\\');

        if (isset(self::$schemaNamesCache[$classNameWithNoLeadingSlash])) {
            return self::$schemaNamesCache[$classNameWithNoLeadingSlash];
        }

        if (isset(self::$schemaNamesCache['\\' . $classNameWithNoLeadingSlash])) {
            return self::$schemaNamesCache['\\' . $classNameWithNoLeadingSlash];
        }

        return null;
    }

    protected function addModelSchema($schemaName, $schemaClassName): SolutionSchema
    {
        // Remove a leading "\" slash if it exists.
        // It will work for most things however in some places where comparisons are
        // drawn with the result of get_class() (which never has a leading slash) the
        // comparisons can fail.

        $schemaClassName = ltrim($schemaClassName, "\\");

        $this->modelSchemaAliases[$schemaName] = $schemaClassName;
        $this->modelSchemaClassNames[$schemaClassName] = $schemaName;

        return $this;
    }

    protected function addRelationship($modelName, $navigationPropertyName, Relationship $relationship)
    {
        $modelName = self::getSchemaClass($modelName);

        if (!isset($this->relationships[$modelName])) {
            $this->relationships[$modelName] = [];
        }

        $this->relationships[$modelName][$navigationPropertyName] = $relationship;
    }

    /**
     * Defines one or more one-to-many relationships in an array structure.
     *
     * e.g.
     *
     * $this->declareOneToManyRelationships(
     * [
     *        "Customer" =>
     *        [
     *            "Orders" => "Order.CustomerID"
     *        ]
     * ] );
     *
     * @param  array $relationships
     * @throws \Rhubarb\Stem\Exceptions\RelationshipDefinitionException
     */
    public function declareOneToManyRelationships($relationships)
    {
        if (!is_array($relationships)) {
            throw new RelationshipDefinitionException("DefineOneToManyRelationships must be passed an array");
        }

        foreach ($relationships as $oneModel => $definitions) {
            $oneModelColumnName = "";

            if (stripos($oneModel, ".") !== false) {
                $parts = explode(".", $oneModel);
                $oneModel = $parts[0];
                $oneModelColumnName = $parts[1];
            }

            foreach ($definitions as $oneNavigationName => $definition) {
                $parts = explode(".", $definition);

                $manyModelName = $parts[0];

                if (stripos($parts[1], ":") !== false) {
                    $subParts = explode(":", $parts[1]);

                    $manyColumnName = $subParts[0];
                    $manyNavigationName = $subParts[1];
                } else {
                    $manyColumnName = $parts[1];
                    $manyNavigationName = $oneModel;
                }

                $this->declareOneToManyRelationship(
                    $oneModel,
                    $oneModelColumnName,
                    $oneNavigationName,
                    $manyModelName,
                    $manyColumnName,
                    $manyNavigationName
                );
            }
        }
    }

    /**
     * Defines one or more one-to-one relationships in an array structure.
     *
     * e.g.
     *
     * $this->declareOneToOneRelationships([
     *     "FirstModel[.FirstModelColumnName - will use FirstModel's UniqueIdentifier Column if not specified]" => [
     *         "NavigationPropertyName" => "SecondModel.SecondModelColumnName[:NavigationPropertyName - will use FirstModel's name if not specified]"
     *     ],
     *     "Staff" => [
     *         "ManagingDirectorCompany" => "Company.ManagingDirectorID:ManagingDirector",
     *         "FinancialDirectorCompany" => "Company.FinancialDirectorID:FinancialDirector"
     *     ]
     * ]);
     *
     * @param  array $relationships
     * @throws \Rhubarb\Stem\Exceptions\RelationshipDefinitionException
     */
    public function declareOneToOneRelationships($relationships)
    {
        if (!is_array($relationships)) {
            throw new RelationshipDefinitionException("DefineOneToOneRelationships must be passed an array");
        }

        foreach ($relationships as $oneModel => $definitions) {
            $oneModelColumnName = "";

            if (stripos($oneModel, ".") !== false) {
                $parts = explode(".", $oneModel);
                $oneModel = $parts[0];
                $oneModelColumnName = $parts[1];
            }

            foreach ($definitions as $oneNavigationName => $definition) {
                $parts = explode(".", $definition);

                $manyModelName = $parts[0];

                if (stripos($parts[1], ":") !== false) {
                    $subParts = explode(":", $parts[1]);

                    $manyColumnName = $subParts[0];
                    $manyNavigationName = $subParts[1];
                } else {
                    $manyColumnName = $parts[1];
                    $manyNavigationName = $oneModel;
                }

                $this->declareOneToOneRelationship(
                    $oneModel,
                    $manyModelName,
                    $oneModelColumnName,
                    $manyColumnName,
                    $oneNavigationName
                );
                $this->declareOneToOneRelationship(
                    $manyModelName,
                    $oneModel,
                    $manyColumnName,
                    $oneModelColumnName,
                    $manyNavigationName
                );
            }
        }
    }

    public function declareManyToManyRelationships($relationships)
    {
        if (!is_array($relationships)) {
            throw new RelationshipDefinitionException("DefineManyToManyRelationships must be passed an array");
        }

        foreach ($relationships as $leftModelName => $definitions) {
            $leftModelColumnName = "";

            if (stripos($leftModelName, ".") !== false) {
                $parts = explode(".", $leftModelName);
                $leftModelName = $parts[0];
                $leftModelColumnName = $parts[1];
            }

            foreach ($definitions as $leftNavigationName => $definition) {
                if (preg_match("/^([^.]+)\.([^_]+)_([^.]+)\.([^:]+):(.+)$/", $definition, $match)) {
                    $joiningModelName = $match[1];
                    $joiningLeftColumnName = $match[2];
                    $joiningRightColumnName = $match[3];
                    $rightModelName = $match[4];
                    $rightColumnName = $joiningRightColumnName;
                    $rightNavigationName = $match[5];


                    // First create two OneToMany relationships on the joining model object
                    $this->declareOneToManyRelationships(
                        [
                            $leftModelName =>
                            [
                                $leftNavigationName . "Raw" => $joiningModelName . "." . $joiningLeftColumnName
                            ]
                        ]
                    );

                    $this->declareOneToManyRelationships(
                        [
                            $rightModelName =>
                            [
                                $rightNavigationName . "Raw" => $joiningModelName . "." . $joiningRightColumnName
                            ]
                        ]
                    );

                    $leftToRight = new ManyToMany(
                        $leftNavigationName,
                        $leftModelName,
                        $joiningLeftColumnName,
                        $joiningModelName,
                        $joiningLeftColumnName,
                        $joiningRightColumnName,
                        $rightModelName,
                        $rightColumnName
                    );

                    $rightToLeft = new ManyToMany(
                        $rightNavigationName,
                        $rightModelName,
                        $joiningRightColumnName,
                        $joiningModelName,
                        $joiningRightColumnName,
                        $joiningLeftColumnName,
                        $leftModelName,
                        $leftModelColumnName
                    );

                    $leftToRight->setOtherSide($rightToLeft);
                    $rightToLeft->setOtherSide($leftToRight);

                    $this->addRelationship($leftModelName, $leftNavigationName, $leftToRight);
                    $this->addRelationship($rightModelName, $rightNavigationName, $rightToLeft);
                }
            }
        }
    }

    /**
     * Defines a one to one relationship from the source to the target model.
     *
     * @param  string $sourceModelName
     * @param  string $targetModelName
     * @param  string $sourceColumnName
     * @param  string $navigationPropertyName
     * @param  string $targetColumnName
     * @return \Rhubarb\Stem\Schema\Relationships\OneToOne
     */
    protected function declareOneToOneRelationship(
        $sourceModelName,
        $targetModelName,
        $sourceColumnName,
        $targetColumnName,
        $navigationPropertyName = ""
    ) {

        $oneToOne = new OneToOne(
            $navigationPropertyName,
            $sourceModelName,
            $sourceColumnName,
            $targetModelName,
            $targetColumnName
        );

        $navigationPropertyName = ($navigationPropertyName) ? $navigationPropertyName : $targetModelName;

        $this->addRelationship(
            $sourceModelName,
            $navigationPropertyName,
            $oneToOne
        );

        return $oneToOne;
    }

    /**
     * Defines a one to many relationship and a one to one reverse relationship.
     *
     * @param    $oneModelName
     * @param    $oneColumnName
     * @param    $oneNavigationName
     * @param    $manyModelName
     * @param    $manyColumnName
     * @param    $manyNavigationName
     * @return   \Rhubarb\Stem\Schema\Relationships\OneToMany
     * @internal param $sourceModelName
     * @internal param $targetModelName
     * @internal param $sourceColumnName
     * @internal param $navigationName
     */
    protected function declareOneToManyRelationship(
        $oneModelName,
        $oneColumnName,
        $oneNavigationName,
        $manyModelName,
        $manyColumnName = "",
        $manyNavigationName = ""
    ) {

        $oneToMany = new OneToMany($oneNavigationName, $oneModelName, $oneColumnName, $manyModelName, $manyColumnName);

        $this->addRelationship(
            $oneModelName,
            $oneNavigationName,
            $oneToMany
        );

        if ($manyColumnName == "") {
            $manyColumnName = $oneColumnName;
        }

        $oneToOne = $this->declareOneToOneRelationship(
            $manyModelName,
            $oneModelName,
            $manyColumnName,
            $oneColumnName,
            $manyNavigationName
        );
        $oneToOne->setOtherSide($oneToMany);
        $oneToMany->setOtherSide($oneToOne);

        return $oneToMany;
    }


    /**
     * Finds and returns a relationship object on a model matching a given navigation name.
     *
     * If no such relationship can be found null is returned.
     *
     * @param  $modelName
     * @param  $navigationName
     * @return null|Relationship
     */
    public function getRelationship($modelName, $navigationName)
    {
        $modelName = $this->getSchemaClass($modelName);

        if (!isset($this->relationships[$modelName])) {
            return null;
        }

        if (!isset($this->relationships[$modelName][$navigationName])) {
            return null;
        }

        return $this->relationships[$modelName][$navigationName];
    }

    /**
     * Asks all registered models to check if the back end schema needs corrected.
     *
     * @param null|int|string $oldVersion This may be a schema version number int or a sha1 hash if per-model versioning is in use
     */
    public function checkModelSchemas($oldVersion = null)
    {
        Model::clearAllRepositories();

        /**
         * @var Model $class
         */
        /**
         * @var Model $object
         */
        foreach ($this->modelSchemaAliases as $class) {
            $object = new $class();

            $repository = $object->getRepository();
            $schema = $repository->getRepositorySchema();
            $schema->checkSchema($repository);

            $class::checkRecords($oldVersion, $this->version);
        }
    }
}
