<?php

namespace Rhubarb\Stem\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Repositories\Repository;

/**
 * Checks equivalence but only if the value is numeric
 *
 * If the comparison value is not numeric then the filter returns all rows.
 */
class NumberEquals extends Equals
{
    protected $isNumeric = false;

    public function __construct($columnName, $equalTo)
    {
        $equalTo = preg_replace("/[^0-9.]/", "", $equalTo);

        $this->isNumeric = is_numeric($equalTo);

        $equalTo = floatval($equalTo);

        parent::__construct($columnName, $equalTo);
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
     * @param $propertiesToAutoHydrate
     */
    protected static function doFilterWithRepository(
        Repository $repository,
        Filter $originalFilter,
        &$params,
        &$propertiesToAutoHydrate
    ) {
        parent::doFilterWithRepository($repository, $originalFilter, $params, $propertiesToAutoHydrate); // TODO: Change the autogenerated stub
    }

    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        if (!$this->isNumeric) {
            return [];
        } else {
            return parent::doGetUniqueIdentifiersToFilter($list);
        }
    }


}