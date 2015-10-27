<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * A class with a string ID
 * @package Rhubarb\Stem\Tests\Fixtures
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
            new String('AccountID', 50),
            new String('AccountName', 50)
        );
        $schema->uniqueIdentifierColumnName = 'AccountID';

        return $schema;
    }
}