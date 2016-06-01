<?php

namespace Rhubarb\Stem\Sql;

class Join extends SqlClause
{
    const JOIN_TYPE_LEFT = "LEFT JOIN";
    const JOIN_TYPE_INNER = "INNER JOIN";
    const JOIN_TYPE_RIGHT = "RIGHT JOIN";

    public $parentColumn = "";
    public $childColumn = "";
    public $joinType = self::JOIN_TYPE_INNER;

    /**
     * @var SqlStatement
     */
    public $statement;

    public function getSql()
    {
        return (string) $this->statement;
    }
}