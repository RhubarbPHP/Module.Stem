<?php

namespace Rhubarb\Stem\Repositories\MySql\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\CreatedIntersectionException;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\Literal;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Sql\LiteralWhereExpression;
use Rhubarb\Stem\Sql\WhereExpressionCollector;

class MySqlLiteral extends Literal
{
    use MySqlFilterTrait;

    protected $literalWhereExpression;

    public function __construct($literalWhereExpression)
    {
        $this->literalWhereExpression = $literalWhereExpression;
    }

    protected static function canFilter(Collection $collection, Repository $repository, $columnName)
    {
        return true;
    }

    protected function doFilterWithRepository(
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        $this->createColumnWhereClauseExpression(null, null, $collection, $repository, $whereExpressionCollector, $params);

        return true;
    }

    protected function createColumnWhereClauseExpression(
        $sqlOperator,
        $value,
        Collection $collection,
        Repository $repository,
        WhereExpressionCollector $whereExpressionCollector,
        &$params
    ) {
        $whereExpressionCollector->addWhereExpression(new LiteralWhereExpression($this->literalWhereExpression));
    }

    /**
     * @param Literal $filter
     * @return MySqlLiteral
     */
    public static function fromGenericFilter(Filter $filter)
    {
        return new self($filter->literalWhereExpression);
    }
}
