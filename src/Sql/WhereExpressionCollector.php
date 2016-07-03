<?php

namespace Rhubarb\Stem\Sql;

interface WhereExpressionCollector
{
    public function addWhereExpression(WhereExpression $expression);
    public function addHavingExpression(WhereExpression $expression);
}