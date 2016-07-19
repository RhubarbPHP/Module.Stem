<?php

namespace Rhubarb\Stem\Sql;

class ColumnWhereExpression extends WhereExpression
{
    public $columnName;
    public $expression;
    public $tableAlias;

    public function __construct($columnName, $expression, $requiredOnHavingClause = false, $tableAlias = null)
    {
        $this->columnName = $columnName;
        $this->expression = $expression;
        $this->onHavingClause = $requiredOnHavingClause;
        $this->tableAlias = $tableAlias;
    }

    public function getSql(SqlStatement $forStatement)
    {
        if ($this->onHavingClause){
            return "`" . $this->columnName . "` " . $this->expression;
        } else {
            $tableAlias = $this->tableAlias;

            if (!$tableAlias){
                $tableAlias = $forStatement->getAlias();
            }

            return "`" . $tableAlias . "`.`" . $this->columnName . "` " . $this->expression;
        }
    }
}