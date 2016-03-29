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
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlIndex;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Columns\JsonColumn;
use Rhubarb\Stem\Schema\Columns\MoneyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Columns\TimeColumn;
use Rhubarb\Stem\Schema\Columns\UUIDColumn;
use Rhubarb\Stem\Schema\Index;
use Rhubarb\Stem\Schema\ModelSchema;

class Company extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblCompany");

        $schema->uniqueIdentifierColumnName = "CompanyID";

        $schema->addColumn(
            new AutoIncrementColumn("CompanyID"),
            new StringColumn("CompanyName", 200),
            new MoneyColumn("Balance"),
            new DateColumn("InceptionDate"),
            new DateTimeColumn("LastUpdatedDate"),
            new TimeColumn("KnockOffTime"),
            new BooleanColumn("BlueChip", false),
            new IntegerColumn("ProjectCount"),
            new JsonColumn("CompanyData"),
            new BooleanColumn("Active", true),
            new UUIDColumn()
        );

        $schema->addIndex(new Index("CompanyID", MySqlIndex::PRIMARY));

        $schema->labelColumnName = "CompanyName";

        return $schema;
    }

    protected function getPublicPropertyList()
    {
        $list = parent::getPublicPropertyList();
        $list[] = "Balance";

        return $list;
    }

    public function getCompanyIDSquared()
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

    protected function createConsistencyValidator()
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
