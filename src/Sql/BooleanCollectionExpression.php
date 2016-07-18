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

    public function getWhereSql(SqlStatement $forStatement)
    {
        $whereExpressions = [];

        foreach($this->whereExpressions as $expression){
            if ($expression->requiredForClause(true)){
                $whereExpressions[] = $expression->getWhereSql($forStatement);
            }
        }

        return implode(" ".$this->boolean." ", $whereExpressions);
    }

    public function getHavingSql(SqlStatement $forStatement)
    {
        $havingExpressions = [];

        foreach($this->whereExpressions as $expression){
            if ($expression->requiredForClause(false)){
                $havingExpressions[] = $expression->getHavingSql($forStatement);
            }
        }

        return implode(" ".$this->boolean." ", $havingExpressions);
    }

    public function addHavingExpression(WhereExpression $expression)
    {
        $expression->onHavingClause = true;
        $this->whereExpressions[] = $expression;
    }
}