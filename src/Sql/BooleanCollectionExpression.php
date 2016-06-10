<?php

namespace Rhubarb\Stem\Sql;

class BooleanCollectionExpression extends WhereExpression implements WhereExpressionCollector
{
    public function __construct($expressions = [])
    {
        if ($expressions){
            $this->whereExpressions = $expressions;
        }
    }

    protected $boolean = "";

    /**
     * @var WhereExpression[]
     */
    public $whereExpressions = [];

    public function getSql(SqlStatement $forStatement)
    {
        return $forStatement->implodeSqlClauses($this->whereExpressions, " ".$this->boolean." ");
    }

    public function addWhereExpression(WhereExpression $expression)
    {
        $this->whereExpressions[] = $expression;
    }
}