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

    protected static function doCanFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter
    )
    {
        return true;
    }

    /**
     * Returns the SQL fragment needed to filter where a column equals a given value.
     *
     * @param Collection $collection
     * @param Repository $repository
     * @param self|Filter $originalFilter
     * @param WhereExpressionCollector $whereExpressionCollector
     * @param array $params
     * @return string|void
     */
    protected static function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        Filter $originalFilter,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        $columnName = self::getRealColumnName($originalFilter, $collection);
        $toAlias = self::getTableAlias($originalFilter, $collection);

        $placeHolder = $originalFilter->detectPlaceHolder($originalFilter->equalTo);
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