<?php

namespace Rhubarb\Stem\Sql;

class LiteralWhereExpression extends WhereExpression
{
    /**
     * @var
     */
    private $sql;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function getSql(SqlStatement $forStatement)
    {
        return $this->sql;
    }
}