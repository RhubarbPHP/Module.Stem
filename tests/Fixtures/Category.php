<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 *
 *
 * @package Rhubarb\Stem\Tests\Fixtures
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class Category extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblCategory");

        $schema->addColumn(
            new AutoIncrement("CategoryID"),
            new String("CategoryName", 50)
        );

        $schema->uniqueIdentifierColumnName = "CategoryID";

        return $schema;
    }
}