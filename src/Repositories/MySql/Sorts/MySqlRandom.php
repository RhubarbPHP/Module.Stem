<?php

namespace Rhubarb\Stem\Repositories\MySql\Sorts;

use Rhubarb\Stem\Collections\Sort;
use Rhubarb\Stem\Sql\ExpressesSqlInterface;

class MySqlRandom extends Sort implements ExpressesSqlInterface {
    public function getSqlExpression()
    {
        return 'RAND()';
    }
}
