<?php

namespace Rhubarb\Stem\Sql;

abstract class SqlClause
{
    public abstract function getSql();

    public function __toString()
    {
        return $this->getSql();
    }
}