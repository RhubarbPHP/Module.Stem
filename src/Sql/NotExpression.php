<?php

namespace Rhubarb\Stem\Sql;

class NotExpression extends WhereExpression
{
    /**
     * @var WhereExpression
     */
    public $expressionToNot;

    public function __construct(WhereExpression $expressionToNot)
    {
        $this->expressionToNot = $expressionToNot;
    }

    public function getSql(SqlStatement $forStatement)
    {
        return "!(".$this->expressionToNot->getSql($forStatement);
    }
}