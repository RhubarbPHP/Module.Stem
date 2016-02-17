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
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;

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
     * Implement to return an array of unique identifiers to filter from the list.
     *
     * @param Collection $list The data list to filter.
     * @return array
     */
    abstract public function doGetUniqueIdentifiersToFilter(Collection $list);

    /**
     * Returns an array of unique identifiers to filter from the list.
     *
     * This will be an empty array if a repository has used this filter to do it's filtering.
     *
     * @param Collection $list The data list to filter.
     * @return array
     */
    public final function getUniqueIdentifiersToFilter(Collection $list)
    {
        if ($this->wasFilteredByRepository()) {
            return [];
        }

        $list->disableRanging();

        $filtered = $this->doGetUniqueIdentifiersToFilter($list);

        $list->enableRanging();

        return $filtered;
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
     * @param \Rhubarb\Stem\Repositories\Repository $repository
     * @param Filter $originalFilter The base filter containing the settings we need.
     * @param array $params An array of output parameters that might be need by the repository, named parameters for PDO for example.
     * @param                                             $propertiesToAutoHydrate
     */
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$propertiesToAutoHydrate
    )
    {

    }

    /**
     * Returns A string containing information needed for a repository to use a filter directly.
     *
     * @param \Rhubarb\Stem\Repositories\Repository $repository
     * @param array $params An array of output parameters that might be need by the repository, named parameters for PDO for example.
     * @param array $propertiesToAutoHydrate An array of properties that need auto hydrated for performance.
     * @return string
     */
    public final function filterWithRepository(Repository $repository, &$params, &$propertiesToAutoHydrate)
    {
        $namespace = $repository->getFiltersNamespace();

        if (!$namespace) {
            return "";
        }

        $parts = explode('\\', $namespace);

        // Get the provider specific implementation of the filter.
        $className = rtrim($namespace, '\\') . '\\' . $parts[count($parts) - 2] . basename(str_replace("\\", "/", get_class($this)));

        if (class_exists($className)) {
            return call_user_func_array($className . "::doFilterWithRepository",
                [$repository, $this, &$params, &$propertiesToAutoHydrate]);
        }

        return "";
    }

    /**
     * Returns a Not filter representing the inverse selection of this filter.
     *
     * @return Not
     */
    public final function getInvertedFilter()
    {
        return new Not($this);
    }

    /**
     * If appropriate, set's the value on the model object such that it is matched by this model.
     *
     * @param \Rhubarb\Stem\Models\Model $model
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
     */
    public static final function speciateFromSettingsArray($settings)
    {
        $type = $settings["class"];
        $filter = $type::fromSettingsArray($settings);

        return $filter;
    }
}
