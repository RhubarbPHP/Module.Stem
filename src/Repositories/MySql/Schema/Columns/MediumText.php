<?php

namespace Rhubarb\Stem\Repositories\MySql\Schema\Columns;

require_once __DIR__ . "/../../../../Schema/Columns/String.php";

/**
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
use Rhubarb\Stem\Schema\Columns\String;

class MediumText extends String
{
    use MySqlColumn;

    public function __construct($columnName)
    {
        parent::__construct($columnName, 0, false);
    }

    public function getDefaultDefinition()
    {
        // MediumText fields can't have a default.
        return "NOT NULL";
    }

    public function getDefinition()
    {
        $sql = "`" . $this->columnName . "` mediumtext " . $this->getDefaultDefinition();

        return $sql;
    }
}
