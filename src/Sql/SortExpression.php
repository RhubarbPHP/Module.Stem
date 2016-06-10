<?php

namespace Rhubarb\Stem\Sql;

class SortExpression extends SqlClause
{
    public $columnName;
    public $ascending = true;

    public function __construct($columnName, $ascending = true)
    {
        $this->columnName = $columnName;
        $this->ascending = $ascending;
    }

    public function getSql(SqlStatement $forStatement)
    {
        $sql = "`".$forStatement->getAlias()."`.`".$this->columnName."`";

        if (!$this->ascending){
            $sql .= " DESC";
        }

        return $sql;
    }
}