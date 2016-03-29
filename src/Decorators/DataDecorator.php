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

namespace Rhubarb\Stem\Decorators;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Stem\Decorators\Formatters\TypeFormatter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Wraps a Model class with support for decoration and formatting.
 *
 * Formatting takes a value and formats it into its correct format for display.
 * e.g. 3 becomes 3.00
 *
 * Decorating take a value and wraps it with a surrounding template.
 * e.g. 4323 becomes <a href="/companies/4323/">Acme Widgets Inc</a>
 *
 * Decorators are designed to be reused and so a factory pattern is used to instantiate the
 * correct decorator and then apply the current model for decoration.
 */
abstract class DataDecorator implements \ArrayAccess
{
    /**
     * A collection of column decorators indexed by column name.
     *
     * @var array
     */
    private $columnDecorators = [];

    /**
     * A collection of column formatters indexed by column name.
     */

    private $columnFormatters = [];

    /**
     * A collection of type decorators indexed by schema column class name.
     *
     * @var array
     */
    private $typeDecorators = [];

    /**
     * A collection of type formatters indexed by schema column class name.
     */

    private $typeFormatters = [];

    /**
     * A static array of all formatters to be applied to a given model's columns
     *
     * Indexed by model class name.
     *
     * @var array
     */
    private static $columnTypeFormatters = [];

    /**
     * A static array of all decorators to be applied to a given model's columns
     *
     * Indexed by model class name.
     *
     * @var array
     */
    private static $columnTypeDecorators = [];

    /**
     * The name of the class being decorated.
     *
     * @var string
     */
    private $modelClass = "";

    /**
     * The object being decorated.
     *
     * @var Model
     */
    protected $model;

    /**
     * The singleton collection of decorators
     *
     * @var DataDecorator[]
     */
    private static $decorators = [];

    private static $decoratorClasses = [];

    private function __construct($modelClass)
    {
        $this->modelClass = $modelClass;

        $this->registerTypeDefinitions();

        // Transpose type definitions into column definitions
        $this->applyTypeDefinitionsToColumns();

        $this->applyColumnTypeAliases();

        $this->registerColumnDefinitions();
    }

    /**
     * Registers a decorator class as the correct type to handle a specific model
     *
     * This can also target model base classes to provide a generic level of decoration.
     *
     * @param $decoratorClassName
     * @param $modelClassName
     */
    final public static function registerDecoratorClass($decoratorClassName, $modelClassName)
    {
        self::$decoratorClasses[ltrim($modelClassName, '\\')] = $decoratorClassName;
    }

    final public static function clearDecoratorClasses()
    {
        self::$decoratorClasses = [];
        self::$decorators = [];
    }

    final public static function getDecoratorForModel(Model $model)
    {
        $class = get_class($model);

        if (!isset(self::$decorators[$class])) {
            $decoratorClass = false;

            // Check for a concrete decorator for this exact class.
            if (isset(self::$decoratorClasses[$class])) {
                $decoratorClass = self::$decoratorClasses[$class];
            } else {
                // Perhaps the decorated was registered with an alias instead?
                $alias = SolutionSchema::getModelNameFromClass($class);

                if (isset(self::$decoratorClasses[$alias])) {
                    $decoratorClass = self::$decoratorClasses[$alias];
                } else {
                    // Descend through the parents to find the best matching decorator.
                    // This could have been much simpler by simply using the instanceof operator
                    // however there's no way to guarantee you would match the most specific decorator, i.e.
                    // the decorator targeting the sub class highest in the target model's hierarchy.
                    $currentClass = new \ReflectionClass($class);

                    do {
                        foreach (self::$decoratorClasses as $modelClassName => $decoratorClassName) {
                            if ($currentClass->getName() == $modelClassName) {
                                $decoratorClass = $decoratorClassName;
                                break 2;
                            }
                        }

                        $currentClass = $currentClass->getParentClass();
                    } while ($currentClass);
                }
            }

            if ($decoratorClass) {
                self::$decorators[$class] = new $decoratorClass($class);
            } else {
                self::$decorators[$class] = false;
            }
        }

        $decorator = self::$decorators[$class];

        if ($decorator) {
            $decorator->model = $model;
        }

        return $decorator;
    }

    protected function getColumnTypeAliases()
    {
        return [];
    }

    protected function applyColumnTypeAliases()
    {
        $columnTypeAliases = $this->getColumnTypeAliases();

        foreach ($columnTypeAliases as $columnName => $columnType) {
            $class = new \ReflectionClass($columnType);

            foreach ($this->typeFormatters as $type => $formatter) {
                if ($class->getName() == $type || $class->isSubclassOf($type)) {
                    $this->columnFormatters[$columnName] = $formatter;
                }
            }

            foreach ($this->typeDecorators as $type => $decorator) {
                if ($class->getName() == $type || $class->isSubclassOf($type)) {
                    $this->columnDecorators[$columnName] = $decorator;
                }
            }
        }
    }

    private function applyTypeDefinitionsToColumns()
    {
        if (!isset(self::$columnTypeFormatters[$this->modelClass])) {
            self::$columnTypeFormatters[$this->modelClass] = [];
            self::$columnTypeDecorators[$this->modelClass] = [];

            $model = SolutionSchema::getModel($this->modelClass);
            $schema = $model->getSchema();
            $columns = $schema->getColumns();

            foreach ($this->typeFormatters as $type => $formatter) {
                foreach ($columns as $columnName => $column) {
                    if ($column instanceof $type) {
                        /**
                         * @var Column $column
                         */

                        if ($formatter instanceof TypeFormatter) {
                            self::$columnTypeFormatters[$this->modelClass][$columnName] = $formatter->getFormatter($column);
                        } else {
                            self::$columnTypeFormatters[$this->modelClass][$columnName] = $formatter;
                        }
                    }
                }
            }

            foreach ($this->typeDecorators as $type => $decorator) {
                foreach ($columns as $columnName => $column) {
                    if ($column instanceof $type) {
                        self::$columnTypeDecorators[$this->modelClass][$columnName] = $decorator;
                    }
                }
            }
        }

        foreach (self::$columnTypeDecorators[$this->modelClass] as $columnName => $decorator) {
            $this->columnDecorators[$columnName] = $decorator;
        }

        foreach (self::$columnTypeFormatters[$this->modelClass] as $columnName => $formatter) {
            $this->columnFormatters[$columnName] = $formatter;
        }
    }

    protected function registerColumnDefinitions()
    {

    }

    protected function registerTypeDefinitions()
    {

    }

    protected function addColumnDecorator($columnNames, \Closure $decoratingCallback)
    {
        if (!is_array($columnNames)) {
            $columnNames = [$columnNames];
        }

        foreach ($columnNames as $columnName) {
            $this->columnDecorators[$columnName] = $decoratingCallback;
        }
    }

    protected function addTypeDecorator($columnType, \Closure $formattingCallback)
    {
        $this->typeDecorators[$columnType] = $formattingCallback;
    }

    protected function addColumnFormatter($columnNames, $formatter)
    {
        if (!is_array($columnNames)) {
            $columnNames = [$columnNames];
        }

        foreach ($columnNames as $columnName) {
            $this->columnFormatters[$columnName] = $formatter;
        }
    }

    protected function addTypeFormatter($columnClass, $formatter)
    {
        $this->typeFormatters[$columnClass] = $formatter;
    }

    public function __get($name)
    {
        $formattedValue = $this->model[$name];

        if (isset($this->columnFormatters[$name])) {
            $function = $this->columnFormatters[$name];
            $formattedValue = $function($this->model, $formattedValue);
        }

        if (isset($this->columnDecorators[$name])) {
            $function = $this->columnDecorators[$name];
            $formattedValue = $function($this->model, $formattedValue);
        }

        return $formattedValue;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return (isset($this->model[$offset]) || isset($this->columnDecorators[$offset]));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value <p> The value to set. </p>
     *                      The value to set.
     * </p>
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new ImplementationException("Decorators cannot be modified");
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new ImplementationException("Decorators cannot be modified");
    }
}
