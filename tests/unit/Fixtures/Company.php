<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

/**
 * A sample data object modelling a company for use with unit testing.
 *
 * @property int $CompanyID
 * @property string $CompanyName
 */
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Models\Validation\HasValue;
use Rhubarb\Stem\Models\Validation\Validator;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlModelSchema;
use Rhubarb\Stem\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Schema\Columns\Boolean;
use Rhubarb\Stem\Schema\Columns\Date;
use Rhubarb\Stem\Schema\Columns\DateTime;
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\Columns\Json;
use Rhubarb\Stem\Schema\Columns\Money;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Columns\Time;
use Rhubarb\Stem\Schema\Columns\UUID;

class Company extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new MySqlModelSchema("tblCompany");

        $schema->uniqueIdentifierColumnName = "CompanyID";

        $schema->addColumn(
            new AutoIncrement("CompanyID"),
            new StringColumn("CompanyName", 200),
            new Money("Balance"),
            new Date("InceptionDate"),
            new DateTime("LastUpdatedDate"),
            new Time("KnockOffTime"),
            new Boolean("BlueChip", false),
            new Integer("ProjectCount"),
            new Json("CompanyData"),
            new Boolean("Active", true),
            new UUID()
        );

        $schema->addIndex(new Index("CompanyID", Index::PRIMARY));

        $schema->labelColumnName = "CompanyName";

        return $schema;
    }

    protected function getPublicPropertyList()
    {
        $list = parent::getPublicPropertyList();
        $list[] = "Balance";

        return $list;
    }

    public function GetCompanyIDSquared()
    {
        return $this->CompanyID * $this->CompanyID;
    }

    protected function getConsistencyValidationErrors()
    {
        $errors = [];

        if (!$this->CompanyName) {
            $errors["CompanyName"] = "Company name must be supplied";
        }

        return $errors;
    }

    protected function CreateConsistencyValidator()
    {
        $validator = new Validator();
        $validator->validations[] = new HasValue("CompanyName");

        return $validator;
    }

    public static function find(Filter $filter = null)
    {
        $activeFilter = new Equals('Active', 1);
        if ($filter === null) {
            $filter = $activeFilter;
        } else {
            $filter = new AndGroup([$filter, $activeFilter]);
        }

        return parent::find($filter);
    }
}