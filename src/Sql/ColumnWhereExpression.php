<?php

namespace Rhubarb\Stem\Sql;

class ColumnWhereExpression extends WhereExpression
{
    public $columnName;
    public $expression;

    public function __construct($columnName, $expression, $requiredOnHavingClause = false)
    {
        $this->columnName = $columnName;
        $this->expression = $expression;
        $this->onHavingClause = $requiredOnHavingClause;
    }

    public function getSql(SqlStatement $forStatement)
    {
        if ($this->onHavingClause){
            return "`" . $this->columnName . "` " . $this->expression;
        } else {
            return "`" . $forStatement->getAlias() . "`.`" . $this->columnName . "` " . $this->expression;
        }
    }
}