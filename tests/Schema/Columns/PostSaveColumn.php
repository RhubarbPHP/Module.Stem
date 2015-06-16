<?php

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\ModelSchema;

class PostSaveColumn extends RhubarbTestCase
{
    public function testSaveMethodIsCalled()
    {
        $test = new TestModel();
        $test->Test = "abc123";

        $test->save();

        $this->assertEquals( "abc123", TestColumn::$tested );
    }
}

class TestModel extends Model
{

    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema( "test" );
        $schema->addColumn( new TestColumn( "Test" ) );

        return $schema;
    }
}

class TestColumn extends Column
{
    public static $tested = false;

    public function getOnSavedCallback()
    {
        return function( $model ){
          self::$tested = $model[ $this->columnName ];
        };
    }
}