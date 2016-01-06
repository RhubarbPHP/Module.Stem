<?php

namespace Rhubarb\Stem\Decorators\Formatters;

use Rhubarb\Stem\Schema\Columns\Column;

abstract class TypeFormatter
{
    /**
     * This method will be called with a column definition, and should return a closure
     * which takes 2 properties - a Model and a value - and returns a formatted value.
     *
     * @param  Column $column
     * @return \Closure
     */
    abstract public function getFormatter(Column $column);
}
