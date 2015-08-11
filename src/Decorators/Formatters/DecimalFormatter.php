<?php

namespace Rhubarb\Stem\Decorators\Formatters;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\Decimal;

class DecimalFormatter extends TypeFormatter
{
    /**
     * This method will be called with a column definition, and should return a closure
     * which takes 2 properties - a Model and a value - and returns a formatted value.
     *
     * @param Decimal|Column $column
     * @return \Closure
     */
    public function getFormatter(Column $column)
    {
        $decimalDigits = $column->getDecimalDigits();

        return function(Model $model, $value) use($decimalDigits) {
            return number_format($value, $decimalDigits);
        };
    }
}