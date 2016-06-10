<?php

namespace Rhubarb\Stem\Sql;

abstract class SqlClause
{
    public abstract function getSql(SqlStatement $forStatement);
}