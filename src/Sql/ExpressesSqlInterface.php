<?php

namespace Rhubarb\Stem\Sql;

/**
 * Implement to provide SQL specific repositories with SQL expressions directly
 */
interface ExpressesSqlInterface
{
    public function getSqlExpression();
}