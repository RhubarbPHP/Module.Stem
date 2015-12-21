<?php

namespace Rhubarb\Stem\Tests\Schema;

use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemModule;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\User;

class SolutionSchemaTest extends ModelUnitTestCase
{
    public function testSchemaMustBeRegistered()
    {
        $this->setExpectedException(SchemaNotFoundException::class);

        SolutionSchema::getSchema("UnRegisteredSchema");
    }

    public function testSchemaRegistration()
    {
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        $schema = SolutionSchema::getSchema("MySchema");

        $this->assertInstanceOf(UnitTestingSolutionSchema::class, $schema);
    }

    public function testInvalidSchemaType()
    {
        SolutionSchema::registerSchema("MyBadSchema", StemModule::class);

        $this->setExpectedException(SchemaRegistrationException::class);

        SolutionSchema::getSchema("MyBadSchema");
    }

    public function testSchemaCache()
    {
        SolutionSchema::clearSchemas();
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        $schema = SolutionSchema::getSchema("MySchema");
        $schema->test = true;

        $schema = SolutionSchema::getSchema("MySchema");

        $this->assertTrue($schema->test);
    }

    public function testGetModelSchema()
    {
        $modelSchema = SolutionSchema::getModelSchema("UnitTestUser");
        $user = new User();

        $this->assertEquals($user->getSchema(), $modelSchema);
    }

    public function testRelationships()
    {
        SolutionSchema::clearSchemas();
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        error_reporting(E_ALL);
        ini_set("display_errors", "on");

        $schema = new UnitTestingSolutionSchema();
        $schema->defineRelationships();

        $relationship = $schema->getRelationship("UnitTestUser", "Company");

        $this->assertInstanceOf(OneToOne::class, $relationship);
        $this->assertInstanceOf(OneToMany::class, $relationship->getOtherSide());

        $relationship = $schema->getRelationship("Company", "Users");

        $this->assertInstanceOf(OneToMany::class, $relationship);

        $relationship = $schema->getRelationship("Company", "Unknown");

        $this->assertNull($relationship);

        $relationship = $schema->getRelationship("Example", "ExampleRelationshipName");

        $this->assertInstanceOf(OneToOne::class, $relationship);

        $columnRelationships = SolutionSchema::getAllOneToOneRelationshipsForModelBySourceColumnName("UnitTestUser");

        $this->assertArrayHasKey("CompanyID", $columnRelationships);
        $this->assertInstanceOf(OneToOne::class, $columnRelationships["CompanyID"]);

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->getRepository()->clearObjectCache();
        $user->Forename = "a";
        $user->save();

        $company->Users->Append($user);

        $b = $user = new User();
        $user->Forename = "b";
        $user->save();

        $company->Users->Append($user);

        // Just to make sure this doesn't get in our relationship!
        $user = new User();
        $user->Forename = "c";
        $user->save();

        $company = new Company($company->CompanyID);

        $this->assertCount(2, $company->Users);
        $this->assertEquals("a", $company->Users[0]->Forename);
        $this->assertEquals("b", $company->Users[1]->Forename);

        $company = $b->Company;

        $this->assertEquals("GCD", $company->CompanyName);
    }

    public function testManyToManyRelationships()
    {

    }

    public function testModelCanBeRetrievedByName()
    {
        $company = SolutionSchema::getModel("Company");

        $this->assertInstanceOf(Company::class, $company);
        $this->assertTrue($company->isNewRecord());

        $company->CompanyName = "Boyo";
        $company->save();

        $model2 = SolutionSchema::getModel("Company", $company->CompanyID);
        $this->assertEquals($company->CompanyID, $model2->UniqueIdentifier);
    }

    public function testSuperseededModelIsReturnedWhenUsingPreviousNamespacedClassName()
    {
        SolutionSchema::registerSchema("SchemaA", __NAMESPACE__ . "\\SchemaA");

        $class = SolutionSchema::getModelClass(__NAMESPACE__ . "\\ModelA");

        $this->assertEquals(__NAMESPACE__ . "\\ModelA", $class);

        SolutionSchema::registerSchema("SchemaB", __NAMESPACE__ . "\\SchemaB");

        $class = SolutionSchema::getModelClass(__NAMESPACE__ . "\\ModelA");

        $this->assertEquals(__NAMESPACE__ . "\\ModelB", $class);
    }
}

class ModelA extends Model
{
    protected function createSchema()
    {
        return new ModelSchema("ModelA");
    }
}

class ModelB extends Model
{
    protected function createSchema()
    {
        return new ModelSchema("ModelB");
    }
}

class SchemaA extends SolutionSchema
{
    public function __construct($version = 0)
    {
        parent::__construct($version);

        $this->addModel("TestModel", __NAMESPACE__ . "\\ModelA");
    }
}

class SchemaB extends SolutionSchema
{
    public function __construct($version = 0)
    {
        parent::__construct($version);

        $this->addModel("TestModel", __NAMESPACE__ . "\\ModelB");
    }
}