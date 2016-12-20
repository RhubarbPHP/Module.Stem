<?php

namespace Rhubarb\Stem\Sql;

use Rhubarb\Stem\Filters\AndGroup;

class SqlStatement extends SqlClause implements WhereExpressionCollector
{
    /**
     * Tracks the use of alias names.
     *
     * @var array
     */
    private static $aliasNames = [];

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

    /**
     * The alias given to the table being selected
     *
     * @var string
     */
    private $alias = "";

    /**
     * A positive number if limiting in the SQL query
     *
     * @var bool|int
     */
    private $limitStart = false;

    /**
     * A positive number if limiting to a certain number of records.
     *
     * @var bool|int
     */
    private $limitCount = false;

    /**
     * A mapping of column alias => [ field, primary key, repository ] for potential use
     * during selection if supporting auto hydration of relationships.
     *
     * @var array
     */
    public $potentialHydrationMappings = [];

    /**
     * Add a where clause expression.
     *
     * @param WhereExpression $where
     * @return AndExpression
     */
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

    /**
     * Add a having clause expressions
     *
     * @param WhereExpression $where
     */
    public function addHavingExpression(WhereExpression $where)
    {
        $where->onHavingClause = true;
        $this->addWhereExpression($where);
    }

    /**
     * Returns the alias in use.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set's the alias to a particular value.
     *
     * @param $aliasName
     */
    public function setAlias($aliasName)
    {
        $this->alias = $aliasName;
    }

    /**
     * Set the limit parameters
     * @param $start
     * @param $count
     */
    public function limit($start, $count)
    {
        $this->limitStart = $start;
        $this->limitCount = $count;
    }

    /**
     * Returns true if this statement is limited
     * @return bool
     */
    public function hasLimit()
    {
        return ($this->limitStart || $this->limitCount);
    }

    /**
     * Utility function to implode a sequence of Sql clauses with glue.
     *
     * @param $clauses
     * @param string $glue
     * @return mixed
     */
    public function implodeSqlClauses($clauses, $glue = ',')
    {
        $statements = [];

        foreach($clauses as $clause){
            $statements[] = $clause->getSql($this);
        }

        return implode($statements, $glue);
    }

    /**
     * Returns an UPDATE statement using the supplied key value pairings to update.
     *
     * Inserts named parameters using the field name but prefixed with "Update"
     *
     * e.g. updating "Forename" should be matched with a named parameter when executing the query of "UpdateForename"
     *
     * @param $fieldsToUpdate
     * @return string
     */
    public function getUpdateSql($fieldsToUpdate)
    {
        $sql = "UPDATE `".$this->schemaName."` AS `".$this->getAlias()."`";

        foreach($this->joins as $join){
            $sql .= " ".$join->joinType." (".$join->getSql($this).") AS `".$join->statement->getAlias()."` ON `".$this->getAlias()."`.`".
                $join->parentColumn."` = `".$join->statement->getAlias()."`.`".$join->childColumn."`";
        }

        $sets = [];

        foreach ($fieldsToUpdate as $key) {
            $paramName = "Update" . $key;

            $sets[] = "`".$this->getAlias()."`.`" . $key . "` = :" . $paramName;
        }

        $sql .= " SET ".implode(",", $sets);

        if ($this->whereExpression) {
            $sql .= " WHERE ".$this->whereExpression->getSql($this);
        }

        return $sql;
    }

    /**
     * Returns the SELECT statement required to fetch the data.
     * @return string
     */
    public function getSelectSql()
    {
        $sql = "SELECT ";

        $sql .= $this->implodeSqlClauses($this->columns, ", ").
            " FROM `".$this->schemaName."` AS `".$this->getAlias()."`";

        foreach($this->joins as $join){
            $joinsSql = $join->getSql($this);
            if (strpos($joinsSql," ") !== false){
                $joinsSql = "(".$joinsSql.")";
            }

            $sql .= " ".$join->joinType." ".$joinsSql." AS `".$join->statement->getAlias()."` ON `".$this->getAlias()."`.`".
                $join->parentColumn."` = `".$join->statement->getAlias()."`.`".$join->childColumn."`";
        }

        if ($this->whereExpression) {
            $havingSql = $this->whereExpression->getWhereSql($this);
            if ($havingSql != "" ){
                $sql .= " WHERE ".$havingSql;
            }
        }
        
        if (count($this->groups)){
            $sql .= " GROUP BY ".$this->implodeSqlClauses($this->groups, ", ");
        }

        if ($this->whereExpression) {
            $havingSql = $this->whereExpression->getHavingSql($this);
            if ($havingSql != "" ){
                $sql .= " HAVING ".$havingSql;
            }
        }

        if (count($this->sorts)){
            $sql .= " ORDER BY ".$this->implodeSqlClauses($this->sorts, ", ");
        }

        if ($this->hasLimit()){
            $sql .= " LIMIT ".$this->limitStart.", ".$this->limitCount;
        }

        return $sql;
    }

    /**
     * Get's the SQL for this clause for the given statement
     * @param SqlStatement $forStatement
     * @return string
     */
    public function getSql(SqlStatement $forStatement)
    {
        return $this->getSelectSql();
    }

    public function __toString()
    {
        return $this->getSelectSql();
    }

    public function getWhereSql(SqlStatement $forStatement)
    {
        // TODO: Implement getWhereSql() method.
    }

    public function getHavingSql(SqlStatement $forStatement)
    {
        // TODO: Implement getHavingSql() method.
    }
}