<?php

namespace Rhubarb\Stem\Sql;

interface WhereExpressionCollector
{
    public function addWhereExpression(WhereExpression $expression);
    public function addHavingExpression(WhereExpression $expression);
    public function getWhereSql(SqlStatement $forStatement);
    public function getHavingSql(SqlStatement $forStatement);
}