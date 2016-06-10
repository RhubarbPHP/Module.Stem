<?php

namespace Rhubarb\Stem\Sql;

class ColumnWhereExpression extends WhereExpression
{
    public $columnName;
    public $expression;

    public function __construct($columnName, $expression)
    {
        $this->columnName = $columnName;
        $this->expression = $expression;
    }

    public function getSql(SqlStatement $forStatement)
    {
        return "`".$forStatement->getAlias()."`.`".$this->columnName."` ".$this->expression;
    }
}