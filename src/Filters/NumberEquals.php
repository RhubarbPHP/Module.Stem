<?php

namespace Rhubarb\Stem\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Repository;

/**
 * Checks equivalence but only if the value is numeric
 *
 * If the comparison value is not numeric then the filter returns all rows.
 */
class NumberEquals extends Equals
{
    protected $isNumeric = false;

    public function __construct($columnName, $equalTo)
    {
        $equalTo = preg_replace("/[^0-9.]/", "", $equalTo);

        $this->isNumeric = is_numeric($equalTo);

        $equalTo = floatval($equalTo);

        parent::__construct($columnName, $equalTo);
    }

    public function evaluate(Model $model)
    {
        if (!$this->isNumeric) {
            return false;
        } else {
            return parent::evaluate($model);
        }
    }
}
