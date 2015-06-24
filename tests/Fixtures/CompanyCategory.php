<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Schema\Columns\Integer;
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
            new AutoIncrement("CompanyCategoryID"),
            new Integer("CompanyID"),
            new Integer("CategoryID")
        );

        $schema->uniqueIdentifierColumnName = "CompanyCategoryID";

        return $schema;
    }
}