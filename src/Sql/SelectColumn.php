<?php

namespace Rhubarb\Stem\Sql;

class SelectColumn extends SelectExpression
{
    public $columnName;
    public $alias;

    public function __construct($columnName = "", $alias = "")
    {
        $this->columnName = $columnName;
        $this->alias = $alias;

        if (!$this->columnName){
            parent::__construct("*");
            return;
        }

        if (strpos($this->columnName, '`') !== false){
            $sql = $this->columnName;
        } else {
            $sql = "`" . $this->columnName . "`";
        }

        if ($this->alias) {
            $sql .= " AS `" . $this->alias . "`";
        }

        parent::__construct($sql);
    }
}