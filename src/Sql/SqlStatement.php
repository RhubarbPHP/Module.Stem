<?php

namespace Rhubarb\Stem\Sql;

use Rhubarb\Stem\Filters\AndGroup;

class SqlStatement extends SqlClause implements WhereExpressionCollector
{
    /**
     * @var SelectExpression[]
     */
    public $columns = [];

    /**
     * @var Join[]
     */
    public $joins = [];

    /**
     * @var string
     */
    public $schemaName;

    /**
     * @var WhereExpression
     */
    public $whereExpression;

    /**
     * @var SortExpression[]
     */
    public $sorts = [];

    /**
     * @var GroupExpression[]
     */
    public $groups = [];

    private $alias = "";

    private $limitStart = false;

    private $limitCount = false;

    public function addWhereExpression(WhereExpression $where)
    {
        if (!($this->whereExpression instanceof AndExpression)){
            $this->whereExpression = new AndExpression($this->whereExpression);
        }

        /**
         * @var AndExpression $andExpression
         */
        $andExpression = $this->whereExpression;
        $andExpression->whereExpressions[] = $where;

        return $andExpression;
    }

    public function getAlias()
    {
        if (!$this->alias){
            $this->alias = uniqid();
        }

        return $this->alias;
    }

    public function limit($start, $count)
    {
        $this->limitStart = $start;
        $this->limitCount = $count;
    }

    public function hasLimit()
    {
        return ($this->limitStart || $this->limitCount);
    }

    public function implodeSqlClauses($clauses, $glue = ',')
    {
        $statements = [];

        foreach($clauses as $clause){
            $statements[] = $clause->getSql($this);
        }

        return implode($statements, $glue);
    }

    public function getStatementSql()
    {
        $sql = "SELECT ";

        $sql .= $this->implodeSqlClauses($this->columns, ", ").
            " FROM `".$this->schemaName."` AS `".$this->getAlias()."`";

        foreach($this->joins as $join){
            $sql .= " ".$join->joinType." (".$join->getSql($this).") AS `".$join->statement->getAlias()."` ON `".$this->getAlias()."`.`".
                $join->parentColumn."` = `".$join->statement->getAlias()."`.`".$join->childColumn."`";
        }

        if ($this->whereExpression) {
            $sql .= " WHERE ".$this->whereExpression->getSql($this);
        }

        if (count($this->sorts)){
            $sql .= " ORDER BY ".$this->implodeSqlClauses($this->sorts, ", ");
        }

        if (count($this->groups)){
            $sql .= " GROUP BY ".$this->implodeSqlClauses($this->groups, ", ");
        }

        if ($this->hasLimit()){
            $sql .= " LIMIT ".$this->limitStart.", ".$this->limitCount;
        }

        return $sql;
    }
    
    public function getSql(SqlStatement $forStatement)
    {
        return $this->getStatementSql();
    }

    public function __toString()
    {
        return $this->getStatementSql();
    }
}