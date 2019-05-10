<?php

namespace Rhubarb\Stem\Sql;

class Join extends SqlClause
{
    const JOIN_TYPE_LEFT = "LEFT JOIN";
    const JOIN_TYPE_INNER = "INNER JOIN";
    const JOIN_TYPE_RIGHT = "RIGHT JOIN";

    public $parentTableAlias = "";
    public $parentColumn = "";
    public $childColumn = "";
    public $joinType = self::JOIN_TYPE_INNER;

    /**
     * @var SqlStatement
     */
    public $statement;

    public function getSql(SqlStatement $forStatement)
    {
        if (count($this->statement->sorts) == 0 &&
            (count($this->statement->groups) == 0) &&
            $this->statement->whereExpression === null &&
            (count($this->statement->columns) == 1 && stripos($this->statement->columns[0]->expression, ".*")!==false) **
            $this->statement->hasLimit() &&
            count($this->statement->joins) == 0
        ) {
            return $this->statement->schemaName;
        }

        return $this->statement->getSelectSql();
    }
}