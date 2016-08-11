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

namespace Rhubarb\Stem\Filters;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\SqlStatement;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

/**
 * The base class for all DataFilters.
 *
 * Filters allow for filtering lists of models with or repository support. This should be
 * preferred to using raw SQL when loading lists as it is much easier to unit test. There are
 * also occasions where offline filtering is actually faster (where the list size is below a
 * certain threshold and a super set of data has already been fetched.
 */
abstract class Filter
{
    /**
     * True if this filter has been used by the repository to filter our list.
     *
     * @var bool
     */
    protected $filteredByRepository = false;

    /**
     * Chooses whether to remove the model from the list or not
     * 
     * Returns true to remove it, false to keep it.
     *
     * @param Model $model
     * @return array
     */
    abstract public function evaluate(Model $model);

    /**
     * Returns true if the list should remove this model from the list because it doesn't match the criteria.
     *
     * @param  Model $model The model to evaluate
     * @return array
     */
    final public function shouldFilter(Model $model)
    {
        if ($this->wasFilteredByRepository()) {
            return [];
        }

        return $this->evaluate($model);
    }

    public function detectPlaceHolder($value)
    {
        if (is_string($value) && strpos($value, "@{") === 0) {
            $field = str_replace("}", "", str_replace("@{", "", $value));

            return $field;
        }

        return false;
    }


    /**
     * Implement this to return a string used by the repository to filter the list.
     *
     * This should only be implemented on an extending class with a namespace of:
     *
     * Rhubarb\Stem\Repositories\[ReposName]\Filters\[FilterName]
     *
     * e.g.
     *
     * Rhubarb\Stem\Repositories\MySql\Filters\Equals
     *
     * @param Repository $repository
     * @param Collection $collection
     * @param Filter $originalFilter The base filter containing the settings we need.
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params An array of output parameters that might be need by the repository, named parameters for PDO for example.
     * @return bool True if repository filtering was possible
     */
    protected static function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        return false;
    }

    /**
     * Return true if the repository can handle this filter.
     *
     * @param Colleciton $collection
     * @param Repository $repository
     * @param Filter $originalFilter
     * @return bool
     */
    protected static function doCanFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter
    ){
        return false;
    }

    final public function canFilterWithRepository(Collection $collection, Repository $repository)
    {
        $namespace = $repository->getFiltersNamespace();

        if (!$namespace) {
            return false;
        }

        $parts = explode('\\', $namespace);

        // Get the provider specific implementation of the filter.
        $className = rtrim($namespace, '\\') . '\\' . $parts[count($parts) - 2] . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            return call_user_func_array(
                $className . "::doCanFilterWithRepository",
                [$collection, $repository, $this]
            );
        }

        return false;
    }

    /**
     * Returns A string containing information needed for a repository to use a filter directly.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param WhereExpressionCollector $sqlStatement
     * @param array $params An array of output parameters that might be need by the repository, named parameters for PDO for example.
     */
    final public function filterWithRepository(Collection $collection, Repository $repository, WhereExpressionCollector $sqlStatement, &$params)
    {
        $namespace = $repository->getFiltersNamespace();

        if (!$namespace) {
            return;
        }

        $parts = explode('\\', $namespace);

        // Get the provider specific implementation of the filter.
        $className = rtrim($namespace, '\\') . '\\' . $parts[count($parts) - 2] . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            $filtered = call_user_func_array(
                $className . "::doFilterWithRepository",
                [$collection, $repository, $this, $sqlStatement, &$params]
            );

            if ($filtered){
                $this->filteredByRepository = true;
            }
        }
    }

    /**
     * Returns a Not filter representing the inverse selection of this filter.
     *
     * @return Not
     */
    final public function getInvertedFilter()
    {
        return new Not($this);
    }

    /**
     * If appropriate, set's the value on the model object such that it is matched by this model.
     *
     * @param  \Rhubarb\Stem\Models\Model $model
     * @return null|Model
     */
    public function setFilterValuesOnModel(Model $model)
    {
        return null;
    }

    /**
     * Returns this filter and any sub filters in a flat array list.
     *
     * @return Filter[]
     */
    public function getAllFilters()
    {
        return [$this];
    }

    /**
     * Returns true if this filter (and if appropriate ALL sub filters) used the repository to filter.
     *
     * @return bool
     */
    public function wasFilteredByRepository()
    {
        return $this->filteredByRepository;
    }

    /**
     * Returns an array of the settings needed to represent this filter.
     */
    public function getSettingsArray()
    {
        return ["class" => get_class($this)];
    }

    public static function fromSettingsArray($settings)
    {
        throw new ImplementationException("This filter doesn't support creation from a settings array");
    }

    /**
     * Create's a filter object of the correct type from the settings array.
     *
     * @param $settings
     * @return mixed
     */
    final public static function speciateFromSettingsArray($settings)
    {
        $type = $settings["class"];
        $filter = $type::fromSettingsArray($settings);

        return $filter;
    }

    /**
     * An opportunity for implementors to create intersections on the collection.
     *
     * @param Collection $collection
     * @param $createIntersectionCallback
     * @throws CreatedIntersectionException
     * @return void
     */
    abstract public function checkForRelationshipIntersections(Collection $collection, $createIntersectionCallback);
}
