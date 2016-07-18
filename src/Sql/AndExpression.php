<?php

namespace Rhubarb\Stem\Sql;

class AndExpression extends BooleanCollectionExpression
{
    protected $boolean = "AND";

    public function requiredForClause($whereOrHaving)
    {
        return true;
    }
}