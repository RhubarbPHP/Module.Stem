<?php

namespace Rhubarb\Stem\Tests\unit\Models;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\DeleteModelException;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\ModelEventManager;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\Account;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\UnitTestingSolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class ModelTest extends ModelUnitTestCase
{
    public function testModelLabelReturnedInToString()
    {
        $example = new TestContact();
        $example->Forename = "George";

        $this->assertEquals("George", (string)$example);
    }

    public function testHasSchema()
    {
        $example = new TestContact();
        $schema = $example->generateSchema();

        // Make sure we have a schema
        $this->assertInstanceOf(ModelSchema::class, $schema);

        // Make sure the unique identifier exists
        $this->assertEquals("ContactID", $schema->uniqueIdentifierColumnName);
    }

    public function testHasUniqueIdentifierColumnName()
    {
        $example = new TestContact();

        $this->assertEquals("ContactID", $example->getUniqueIdentifierColumnName());
        $this->assertEquals("ContactID", $example->UniqueIdentifierColumnName);

        $example->ContactID = 4;

        $this->assertEquals(4, (int)$example->UniqueIdentifier);
    }

    public function testModelHasLabel()
    {
        $user = new User();
        $user->Forename = "Andrew";
        $user->Surname = "Cuthbert";

        $this->assertEquals("Andrew Cuthbert", $user->getLabel());
    }

    public function testModelReloads()
    {
        $user = new User();
        $user->Forename = "Bob";
        $user->save();

        $secondUser = new User($user->UserID);

        $user->Forename = "James";
        $user->save();

        $secondUser->reload();

        $this->assertEquals("James", $secondUser->Forename);
    }

    public function testNewRecordIsFlaggedAndObjectCanBeLoadedByIdentifier()
    {
        $test = new TestContact();

        $this->assertTrue($test->isNewRecord());

        $test->save();

        $this->assertFalse($test->isNewRecord());

        $id = $test->ContactID;

        $test2 = new TestContact($id);

        $this->assertFalse($test2->isNewRecord());
    }

    public function testModelImportsData()
    {
        $test = new TestContact();
        $test->Forename = "Andrew";
        $test->Town = "Belfast";

        $data = [
            "Forename" => "John",
            "Surname" => "Smith",
            "DateOfBirth" => "today"
        ];

        $test->importData($data);

        $this->assertEquals("John", $test->Forename);
        $this->assertEquals("Smith", $test->Surname);
        $this->assertEquals(date("Y-m-d"), $test->DateOfBirth->format("Y-m-d"));
        $this->assertEquals("Belfast", $test->Town);
    }

    public function testLoadingMissingRecordThrowsException()
    {
        $this->setExpectedException(RecordNotFoundException::class);

        new TestContact(55);
    }

    public function testLoadingZeroRecordThrowException()
    {
        $this->setExpectedException(RecordNotFoundException::class);

        new TestContact(0);
    }

    public function testDataCanPersist()
    {
        $test = new TestContact();
        $test->Forename = "Andrew";
        $test->save();

        $contactId = $test->ContactID;

        $this->assertGreaterThan(0, $contactId);
    }

    public function testSupportsGetters()
    {
        $model = new TestContact();

        $this->assertEquals("TestValue", $model->MyTestValue);
    }

    public function testSupportsSetters()
    {
        $model = new TestContact();
        $model->Name = "Andrew Cuthbert";

        $this->assertEquals("ANDREW CUTHBERT", $model->Name);
    }

    public function testRelationships()
    {
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        $company = new Company();
        $company->CompanyName = "Test Company";
        $company->save();

        $user = new User();
        $user->Username = "jdoe";
        $user->Password = "asdfasdf";
        $user->Active = 1;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $user = new User();
        $user->Username = "msmith";
        $user->Password = "";
        $user->Active = 1;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $company = $user->Company;

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals("Test Company", $company->CompanyName);

        $users = $company->Users;

        $this->assertCount(2, $users);
        $this->assertEquals("msmith", $users[1]->Username);
    }

    public function testNavigationByDotOperator()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->Username = "abc";

        $company->Users->append($user);

        $this->assertEquals("GCD", $user["Company.CompanyName"]);
    }

    public function testSetByDotOperator()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $user = new User();
        $user->Username = "abc";

        $company->Users->append($user);

        $user["Company.CompanyName"] = "ABC";

        $this->assertEquals("ABC", $user->Company->CompanyName);
    }

    public function testModelReportsIsConsistency()
    {
        $company = new Company();

        $this->assertFalse($company->isConsistent(false), "Companies with no name aren't consistent - this should be false.");

        $company->CompanyName = "Betsy";

        $this->assertTrue($company->isConsistent());
    }

    public function testModelReturnsConsistencyErrors()
    {
        $company = new Company();
        $errors = [];

        try {
            $company->isConsistent();
        } catch (ModelConsistencyValidationException $er) {
            $errors = $er->getErrors();
        }

        $this->assertCount(1, $errors);
        $this->assertEquals("CompanyName", key($errors));
    }

    public function testCanBeFound()
    {
        $user = new User();
        $user->Username = "abc";
        $user->Active = true;
        $user->save();

        $user = new User();
        $user->Username = "def";
        $user->Active = true;
        $user->save();

        $user = new User();
        $user->Username = "ghi";
        $user->Active = true;
        $user->save();

        $user = User::fromUsername("def");

        $this->assertEquals("def", $user->Username);

        $this->setExpectedException(RecordNotFoundException::class);

        User::fromUsername("123");
    }

    public function testPublicProperties()
    {
        $example = new TestContact();
        $example->ContactID = 3;
        $example->Forename = "abc";
        $example->Surname = "123";
        $example->DateOfBirth = "2010-01-01";

        $data = $example->exportPublicData();

        // Date of birth should not be in here!
        $this->assertEquals(["ContactID" => 3, "Forename" => "abc", "Surname" => "123"], $data);
    }

    public function testModelsCanBeDeleted()
    {

        $example = new TestContact();

        $repository = $example->getRepository();
        $repository->clearObjectCache();

        $example->save();

        $this->assertCount(1, new RepositoryCollection("TestContact"));

        $example->delete();

        $this->assertCount(0, new RepositoryCollection("TestContact"));

        // Test that deleting a new model throws an exception.

        $this->setExpectedException(DeleteModelException::class);

        $example = new TestContact();
        $example->delete();
    }

    public function testModelEventing()
    {
        $example = new TestContact();

        $product = 0;

        ModelEventManager::attachEventHandler("TestContact", "Test", function ($model, $x, $y, $z) use (&$product) {
            $product = $x * $y * $z;
        });

        $example->simulateRaiseEvent("Test", 1, 2, 3);

        $this->assertEquals(6, $product);

        $product = 0;

        $example->simulateRaiseEventAfterSave("Test", 2, 3, 4);

        $this->assertEquals(0, $product);

        $example->save();

        $this->assertEquals(24, $product);

        $product = 0;

        $example->a = "b";
        $example->save();

        $this->assertEquals(0, $product);
    }

    public function testModelThrowsSaveEvent()
    {
        $example = new TestContact();
        $example->Forename = "Bob";

        $saved = false;

        ModelEventManager::attachEventHandler('TestContact', 'AfterSave', function () use (&$saved) {
            $saved = true;
        });

        $example->save();

        $this->assertTrue($saved);
    }

    public function testModelDoesntSaveIfHasntChanged()
    {
        $example = new TestContact();
        $example->Forename = "Bob";
        $example->save();

        $saved = false;

        ModelEventManager::attachEventHandler('TestContact', 'AfterSave', function () use (&$saved) {
            $saved = true;
        });

        $example->save();

        $this->assertFalse($saved);
    }

    public function testGetColumnSchema()
    {
        $example = new TestContact();

        $schema = $example->getModelColumnSchemaForColumnReference("Forename");

        $this->assertInstanceOf(StringColumn::class, $schema);
        $this->assertEquals("Forename", $schema->columnName);

        $schema = $example->getModelColumnSchemaForColumnReference("ExampleRelationshipName.InceptionDate");
        $this->assertInstanceOf(DateColumn::class, $schema);
        $this->assertEquals("InceptionDate", $schema->columnName);
    }

    public function testOnLoad()
    {
        $example = new TestContact();

        $this->assertFalse($example->loaded);

        $example->save();

        $example = new TestContact($example->UniqueIdentifier);
        $this->assertTrue($example->loaded);


        // This is the old, bad pattern. Unless this can be justified - importing into a new record is NOT loading.
        $example = new TestContact();
        $example->importRawData(["a" => "b"]);

        $this->assertFalse($example->loaded);

        $example->importRawData([$example->UniqueIdentifierColumnName => 2]);

        $this->assertFalse($example->loaded);
    }

    public function testModelGetsDefaultValues()
    {
        $example = new TestContact();

        $this->assertTrue(0 === $example->CompanyID);
    }

    public function testModelCanBeCloned()
    {
        $contact = new TestContact();
        $contact->save();

        $newContact = clone $contact;

        $this->assertTrue($newContact->isNewRecord());
    }

    public function testIsNewRecordFlagWithNonAutoIncrementID()
    {
        $account = new Account();
        $account->AccountID = 'test1';
        $account->AccountName = 'test 1';

        $this->assertTrue($account->isNewRecord());
        $account->save();
        $this->assertFalse($account->isNewRecord());

        $accountReload = new Account('test1');
        $this->assertFalse($accountReload->isNewRecord());
        $accountReload->AccountName = 'test 1-1';
        $accountReload->save();
        $this->assertFalse($accountReload->isNewRecord());

        $accountImport = new Account();
        $this->assertTrue($accountImport->isNewRecord());
        $accountImport->importRawData([
            'AccountName' => 'Account 2',
            'AccountID' => 'Account2',
        ]);
        $this->assertTrue($accountImport->isNewRecord());
        $accountImport->save();
        $this->assertFalse($accountImport->isNewRecord());
    }
}
