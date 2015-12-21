<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * A class with a string ID
 * @package Rhubarb\Stem\Tests\unit\Fixtures
 *
 * @property string $AccountID
 * @property string $AccountName
 */
class Account extends Model
{

    /**
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema('tblAccount');
        $schema->addColumn(
            new StringColumn('AccountID', 50),
            new StringColumn('AccountName', 50)
        );
        $schema->uniqueIdentifierColumnName = 'AccountID';

        return $schema;
    }
}