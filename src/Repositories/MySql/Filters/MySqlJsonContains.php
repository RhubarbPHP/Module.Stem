<?php

namespace Rhubarb\Stem\Repositories\MySql\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\JsonContains;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlJsonContains extends JsonContains
{
    use MySqlFilterTrait {
        MySqlFilterTrait::getTransformedComparisonValueForRepository as getTransformedComparisonValueForRepositoryTrait;
    }

    public static function fromGenericFilter(Filter $filter)
    {
        /**
         * @var JsonContains $filter
         */
        return new static($filter->columnName, $filter->contains, $filter->caseSensitive);
    }

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params
     * @return string|void
     */
    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        return $this->createColumnWhereClauseExpression(
            "LIKE",
            "%" . $this->contains . "%",
            $collection,
            $repository,
            $whereExpressionCollector,
            $params);
    }

    public function getTransformedComparisonValueForRepository($columnName, $rawComparisonValue, Repository $repository, Collection $collection)
    {
        return $rawComparisonValue;
    }
}
