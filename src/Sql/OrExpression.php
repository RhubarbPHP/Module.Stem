<?php

namespace Rhubarb\Stem\Sql;

class OrExpression extends BooleanCollectionExpression
{
    protected $boolean = "OR";

    public function requiredForClause($whereOrHaving)
    {
        foreach($this->whereExpressions as $expression){
            if (!$expression->requiredForClause($whereOrHaving)){
                return false;
            }
        }

        return true;
    }
}