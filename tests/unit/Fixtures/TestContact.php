<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\DecimalColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Columns\TimeColumn;
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
class TestContact extends Model
{
    public $loaded = false;

    protected function createSchema()
    {
        $schema = new ModelSchema("tblContact");

        $schema->addColumn(
            new AutoIncrementColumn("ContactID"),         
            new IntegerColumn("CompanyID", 0),
            new DateColumn("DateOfBirth"),
            new DateTimeColumn("CreatedDate"),
            new StringColumn("Forename", 100),
            new StringColumn("Surname", 100),
            new BooleanColumn("KeyContact"),
            new TimeColumn("CoffeeTime"),
            new DecimalColumn("CreditLimit", 10, 2),
            new DecimalColumn("Balance", 8, 4),
            new StringColumn("Postcode", 50),
            new StringColumn("AddressLine1", 50)
        );

        $schema->uniqueIdentifierColumnName = "ContactID";
        $schema->labelColumnName = "Forename";

        return $schema;
    }

    protected function onLoaded()
    {
        $this->loaded = true;
    }

    public function simulateRaiseEvent($eventName)
    {
        call_user_func_array([$this, "raiseEvent"], func_get_args());
    }

    public function simulateRaiseEventAfterSave($eventName)
    {
        call_user_func_array([$this, "raiseEventAfterSave"], func_get_args());
    }

    protected function getPublicPropertyList()
    {
        $properties = parent::getPublicPropertyList();
        $properties[] = "Surname";

        return $properties;
    }

    public function setName($name)
    {
        $this->modelData["Name"] = strtoupper($name);
    }

    public function getMyTestValue()
    {
        return "TestValue";
    }
}
