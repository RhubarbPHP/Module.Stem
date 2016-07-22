<?php

namespace Rhubarb\Stem\Sql;

class SelectExpression extends SqlClause
{
    public $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function getSql(SqlStatement $forStatement)
    {
        return $this->expression;
    }
}