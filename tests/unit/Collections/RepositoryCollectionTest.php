<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\ArrayCollection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class RepositoryCollectionTest extends ModelUnitTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setupData();
    }

    public function testCollectionSorts()
    {
        $collection = Example::find()->addSort("Forename", false);

        $this->assertEquals("Mary", $collection[0]->Forename);
    }

    public function testCollectionFilters()
    {
        $collection = new RepositoryCollection(Example::class);

        $this->assertCount(4, $collection);

        $collection->filter(new Equals("Forename", "John"));

        $this->assertCount(2, $collection);

    }

    public function testIntersections()
    {
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
    }

    public function testAggregates()
    {
        $collection = Company::all();
        $collection->intersectWith(
            Example::all()
                ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(1, $collection[1]->CountOfContacts);
        $this->assertEquals(2, $collection[0]->CountOfContacts);

        $collection = Company::all();
        $collection->intersectWith(
            Example::all()
                ->addAggregateColumn(new Count("Contacts"))
                ->addAggregateColumn(new Sum("CompanyID")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts", "SumOfCompanyID"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(2, $collection[1]->SumOfCompanyID);
        $this->assertEquals(2, $collection[0]->SumOfCompanyID);
    }

    public function testLimits()
    {
        $collection = Company::all();
        $collection->setRange(0, 2);

        $x = 0;
        foreach($collection as $item){
            $x++;
        }

        $this->assertEquals(2, $x);
        $this->assertEquals(3, count($collection));
    }

    public function testIntersectionsWithNonRepositoryCollections()
    {
        $collection = Company::all();
        $contacts = Example::all();
        $contrivedArray = [];

        foreach($contacts as $contact){
            $contrivedArray[] = $contact;
        }

        $collection->intersectWith(
            (new ArrayCollection("Example", $contrivedArray))
            ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(1, $collection[1]->CountOfContacts);
        $this->assertEquals(2, $collection[0]->CountOfContacts);
    }

    public function testDotNotationOnFilters()
    {
        $collection = new RepositoryCollection(Company::class);
        $collection->filter(new Equals("Contacts.CompanyID", 2));

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->CompanyID);
    }

    protected function setupData()
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