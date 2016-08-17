<?php

namespace Rhubarb\Stem\Repositories\MySql\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\ColumnIntersectsCollection;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\ColumnWhereExpression;
use Rhubarb\Stem\Sql\LiteralWhereExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlColumnIntersectsCollection extends ColumnIntersectsCollection
{
    use MySqlFilterTrait;

    protected function doCanFilterWithRepository(
        Collection $collection,
        Repository $repository
    )
    {
        return true;
    }

    public static function fromGenericFilter(Filter $filter)
    {
        /**
         * @var ColumnIntersectsCollection $filter
         */
        return new static($filter->columnName, $filter->collection);
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
        $columnName = self::getRealColumnName($this, $collection);
        $toAlias = self::getTableAlias($this, $collection);

        $placeHolder = $this->detectPlaceHolder($this->equalTo);
        // The placeholder will contain an _ to denote the alias given to it in ColumnIntersectsCollection. We
        // need to do a normal where clause on it here, rather than a having clause so we need to explode this
        // and create a direct reference to the column.
        $parts = explode("_",$placeHolder);
        $target = "`".$collection->getUniqueReference()."`.`".$parts[1]."`";

        $columnName = "`".$toAlias."`.`".$columnName."`";

        $whereExpressionCollector->addWhereExpression(
            new LiteralWhereExpression(
                $columnName." IS NOT NULL AND ".$columnName." = ".$target, false, $toAlias));

        return true;
    }
}