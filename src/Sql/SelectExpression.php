<?php

namespace Rhubarb\Stem\Sql;

class SelectExpression extends SqlClause
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function getSql(SqlStatement $forStatement)
    {
        return $this->expression;
    }
}