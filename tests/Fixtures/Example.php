<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Boolean;
use Rhubarb\Stem\Schema\Columns\Date;
use Rhubarb\Stem\Schema\Columns\DateTime;
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\Columns\Time;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * Just a test data object to use and abuse in unit tests.
 *
 * @property int $ContactID
 * @property int $CompanyID
 * @property string $Forename
 * @property string $Surname
 * @property \Date $DateOfBirth
 */
class Example extends Model
{
    public $loaded = false;

    protected function createSchema()
    {
        $schema = new ModelSchema("tblContact");

        $schema->addColumn(
            new Integer("ContactID", 0),
            new Integer("CompanyID", 0),
            new Date("DateOfBirth"),
            new DateTime("CreatedDate"),
            new String("Forename", 100),
            new String("Surname", 100),
            new Boolean("KeyContact"),
            new Time("CoffeeTime")
        );

        $schema->uniqueIdentifierColumnName = "ContactID";
        $schema->labelColumnName = "Forename";

        return $schema;
    }

    protected function onLoaded()
    {
        $this->loaded = true;
    }

    public function SimulateRaiseEvent($eventName)
    {
        call_user_func_array([$this, "raiseEvent"], func_get_args());
    }

    public function SimulateRaiseEventAfterSave($eventName)
    {
        call_user_func_array([$this, "raiseEventAfterSave"], func_get_args());
    }

    protected function getPublicPropertyList()
    {
        $properties = parent::getPublicPropertyList();
        $properties[] = "Surname";

        return $properties;
    }

    public function SetName($name)
    {
        $this->modelData["Name"] = strtoupper($name);
    }

    public function GetMyTestValue()
    {
        return "TestValue";
    }
}
