<?php

namespace Rhubarb\Stem\Sql;

abstract class WhereExpression extends SqlClause
{
    /**
     * Set to true to indicate the expression must be used in a having clause after aggregation
     *
     * @var bool
     */
    public $onHavingClause = false;

    public function requiredForClause($whereOrHaving)
    {
        if ($whereOrHaving){
            return !$this->onHavingClause;
        } else {
            return $this->onHavingClause;
        }
    }

    public function getWhereSql(SqlStatement $forStatement)
    {
        if (!$this->onHavingClause){
            return $this->getSql($forStatement);
        }
    }

    public function getHavingSql(SqlStatement $forStatement)
    {
        if ($this->onHavingClause){
            return $this->getSql($forStatement);
        }
    }
}