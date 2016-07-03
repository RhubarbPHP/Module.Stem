<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class TestDonation extends Model
{

    /**
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema('tblDonation');
        $schema->addColumn(
            new AutoIncrementColumn('DonationID'),
            new ForeignKeyColumn("ContactID"),
            new DateColumn("DonationDate")
        );

        return $schema;
    }
}