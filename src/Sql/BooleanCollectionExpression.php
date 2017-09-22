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
        $sql = $forStatement->implodeSqlClauses($this->whereExpressions, " ".$this->boolean." ");

        if (count($this->whereExpressions) > 1){
            $sql = "(".$sql.")";
        }

        return $sql;
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

        $expression = implode(" " . $this->boolean . " ", $whereExpressions);

        if (count($whereExpressions) > 1) {
            $expression = "(" . $expression . ")";
        }

        return $expression;    }

    public function getHavingSql(SqlStatement $forStatement)
    {
        $havingExpressions = [];

        foreach($this->whereExpressions as $expression){
            if ($expression->requiredForClause(false)){
                if($expression->getHavingSql($forStatement) != "")
                {
                    $havingExpressions[] = $expression->getHavingSql($forStatement);
                }
            }
        }

        $expression = implode(" ".$this->boolean." ", $havingExpressions);

        if (count($havingExpressions) > 1) {
            $expression = "(".$expression.")";
        }

        return $expression;
    }

    public function addHavingExpression(WhereExpression $expression)
    {
        $expression->onHavingClause = true;
        $this->whereExpressions[] = $expression;
    }
}