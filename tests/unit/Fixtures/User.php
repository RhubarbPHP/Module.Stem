<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DecimalColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * @property int $UserID
 * @property int $CompanyID
 * @property string $Username
 * @property string $Forename
 * @property string $Surname
 * @property string $Password
 * @property bool $Active
 */
class User extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblUser");

        $schema->addColumn(
            new AutoIncrementColumn("UserID"),
            new ForeignKeyColumn("CompanyID"),
            new MySqlEnumColumn("UserType", "Staff", ["Staff", "Administrator"]),
            new StringColumn("Username", 40),
            new StringColumn("Forename", 40),
            new StringColumn("Surname", 40),
            new StringColumn("Password", 120),
            new BooleanColumn("Active", false),
            new DecimalColumn("Wage")
        );

        $schema->uniqueIdentifierColumnName = "UserID";
        $schema->labelColumnName = "FullName";

        return $schema;
    }

    public function GetBigWage()
    {
        return $this->Wage * 10;
    }

    public function GetFullName()
    {
        return $this->Forename . " " . $this->Surname;
    }

    public static function FromUsername($username)
    {
        return self::findFirst(new Equals("Username", $username));
    }

    public static function find(Filter $filter = null)
    {
        $activeFilter = new Equals('Active', true);
        if ($filter === null) {
            $filter = $activeFilter;
        } else {
            $filter = new AndGroup([$filter, $activeFilter]);
        }

        return parent::find($filter);
    }
}