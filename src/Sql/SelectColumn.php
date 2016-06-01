<?php

namespace Rhubarb\Stem\Sql;

class SelectColumn extends SqlClause
{
    public $columnName;
    public $alias;

    public function __construct($columnName = "", $alias = "")
    {
        $this->columnName = $columnName;
        $this->alias = $alias;
    }

    public function getSql()
    {
        if (!$this->columnName){
            return "*";
        }

        if (strpos($this->columnName, '`') !== false){
            $sql = $this->columnName;
        } else {
            $sql = "`" . $this->columnName . "`";
        }

        if ($this->alias) {
            $sql .= " AS `" . $this->alias . "`";
        }

        return $sql;
    }
}