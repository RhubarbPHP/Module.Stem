<?php

namespace Rhubarb\Stem\Repositories\MySql\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\OneOfCollection;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlOneOfCollection extends OneOfCollection
{
    use MySqlFilterTrait;

    /**
     * @param Filter|self $filter
     *
     * @return bool|Filter|static
     */
    public static function fromGenericFilter(Filter $filter)
    {
        return new static($filter->columnName, $filter->collection, $filter->collectionColumnName);
    }

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection               $collection
     * @param Repository|Mysql         $repository
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array                    $params
     *
     * @return bool
     * @throws FilterNotSupportedException
     */
    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        $columnName = $this->columnName;

        if (self::canFilter($collection, $repository, $columnName)) {
            $aliases = $collection->getPulledUpAggregatedColumns();
            $isAlias = in_array($columnName, $aliases);

            $columnName = self::getRealColumnName($this, $collection);
            $toAlias = self::getTableAlias($this, $collection);

            $subQuery = $repository->getSqlStatementForCollectionWithColumns(
                $this->collection,
                $params,
                [$this->collectionColumnName]
            );

            $whereExpressionCollector->addWhereExpression(
                new ColumnWhereExpression($columnName, " IN ( {$subQuery} )", $isAlias, $toAlias)
            );

            return true;
        }

        return false;
    }
}

