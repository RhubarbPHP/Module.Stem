<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class TestDeclaration extends Model
{
    /**
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema('tblDeclaration');
        $schema->addColumn(
            new AutoIncrementColumn('DeclarationID'),
            new ForeignKeyColumn("DonationID"),
            new ForeignKeyColumn("ContactID"),
            new DateColumn("StartDate"),
            new DateColumn("EndDate"),
            new BooleanColumn("Cancelled")
        );

        return $schema;
    }
}