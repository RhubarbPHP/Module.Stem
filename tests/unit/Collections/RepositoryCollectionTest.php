<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class RepositoryCollectionTest extends ModelUnitTestCase
{
    public function testCollectionSorts()
    {
        $this->setupData();

        $collection = Example::find()->addSort("Forename", false);

        $this->assertEquals("Mary", $collection[0]->Forename);
    }

    public function testCollectionFilters()
    {
        $this->setupData();

        $collection = new RepositoryCollection(Example::class);

        $this->assertCount(4, $collection);

        $collection->filter(new Equals("Forename", "John"));

        $this->assertCount(2, $collection);

        $collection = new RepositoryCollection(Example::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID");

        $this->assertCount(1, $collection);
        $this->assertEquals("Mary", $collection[0]->Forename);

        $collection = new RepositoryCollection(Example::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID", ["Balance"]);

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->Balance);

        $collection = new RepositoryCollection(Example::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID", ["Balance" => "CompanyBalance"]);

        $this->assertEquals(2, $collection[0]->CompanyBalance);

        $collection = Company::all();
        $collection->intersectWith(
            Example::all()
                ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(2, $collection[0]->CountOfContacts);
        $this->assertEquals(1, $collection[1]->CountOfContacts);


    }

    private function setupData()
    {
        $company = new Company();
        $company->CompanyName = "C1";
        $company->Balance = 1;
        $company->save();

        $company = new Company();
        $company->CompanyName = "C2";
        $company->Balance = 2;
        $company->save();

        $company = new Company();
        $company->CompanyName = "C3";
        $company->Balance = 3;
        $company->save();

        $contact = new Example();
        $contact->Forename = "John";
        $contact->CompanyID = 1;
        $contact->save();

        $contact = new Example();
        $contact->Forename = "Mary";
        $contact->CompanyID = 2;
        $contact->save();

        $contact = new Example();
        $contact->Forename = "Jule";
        $contact->CompanyID = 3;
        $contact->save();

        $contact = new Example();
        $contact->Forename = "John";
        $contact->CompanyID = 1;
        $contact->save();
    }
}