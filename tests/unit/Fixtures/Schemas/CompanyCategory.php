<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class CompanyCategory extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblCompanyCategory");
        $schema->addColumn(
            new AutoIncrementColumn("CompanyCategoryID"),
            new IntegerColumn("CompanyID"),
            new IntegerColumn("CategoryID")
        );

        $schema->uniqueIdentifierColumnName = "CompanyCategoryID";

        return $schema;
    }
}