<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\ArrayCollection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\TestDeclaration;
use Rhubarb\Stem\Tests\unit\Fixtures\TestDonation;

class RepositoryCollectionTest extends ModelUnitTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setupData();
    }

    public function testCollectionSorts()
    {
        $collection = TestContact::find()->addSort("Forename", false);

        $this->assertEquals("Mary", $collection[0]->Forename);
    }

    public function testCollectionFilters()
    {
        $collection = new RepositoryCollection(TestContact::class);

        $this->assertCount(5, $collection);

        $collection->filter(new Equals("Forename", "John"));

        $this->assertCount(2, $collection);
    }

    public function testIntersections()
    {
        $collection = new RepositoryCollection(TestContact::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID");

        $this->assertCount(1, $collection);
        $this->assertEquals("Mary", $collection[0]->Forename);

        $collection = new RepositoryCollection(TestContact::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID", ["Balance"]);

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->Balance);

        $collection = new RepositoryCollection(TestContact::class);
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID", ["Balance" => "CompanyBalance"]);

        $this->assertEquals(2, $collection[0]->CompanyBalance);
    }

    public function testAggregates()
    {
        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(1, $collection[1]->CountOfContacts);
        $this->assertEquals(3, $collection[0]->CountOfContacts);

        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts"))
                ->addAggregateColumn(new Sum("CompanyID")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts", "SumOfCompanyID"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(2, $collection[1]->SumOfCompanyID);
        $this->assertEquals(3, $collection[0]->SumOfCompanyID);

        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);
        $collection->filter(new Equals("CountOfContacts", 3));

        $this->assertCount(1, $collection);
        $this->assertEquals(3, $collection[0]->CountOfContacts);
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
        $contacts = TestContact::all();
        $contrivedArray = [];

        foreach($contacts as $contact){
            $contrivedArray[] = $contact;
        }

        $collection->intersectWith(
            (new ArrayCollection("TestContact", $contrivedArray))
            ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(1, $collection[1]->CountOfContacts);
        $this->assertEquals(3, $collection[0]->CountOfContacts);
    }

    public function testSortOnAggregate()
    {
        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts")),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);
        $collection->addSort("CountOfContacts", false);
        $collection->addSort("CompanyID", true);

        $this->assertCount(3, $collection);
        $this->assertEquals(1, $collection[0]->UniqueIdentifier);
        $this->assertEquals(2, $collection[1]->UniqueIdentifier);
    }

    public function testDotNotationOnFilters()
    {
        $collection = new RepositoryCollection(Company::class);
        $collection->filter(new Equals("Contacts.CompanyID", 2));

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->CompanyID);

        $collection = new RepositoryCollection(Company::class);
        $collection->filter(new Equals("Contacts.Forename", "Mary"));

        $this->assertCount(1, $collection);
        $this->assertEquals(2, $collection[0]->CompanyID);

        $collection = new RepositoryCollection(TestContact::class);
        $collection->filter(new Equals("Company.CompanyName", "C2"));

        $this->assertCount(1, $collection);
        $this->assertEquals("Mary", $collection[0]->Forename);

        $collection = new RepositoryCollection(TestContact::class);
        $collection->filter(new Equals("Company.Contacts.Forename", "Babs"));

        $this->assertCount(3, $collection);
        $this->assertEquals("John", $collection[0]->Forename);
        $this->assertEquals("John", $collection[1]->Forename);
        $this->assertEquals("Babs", $collection[2]->Forename);
    }

    public function testDotNotationOnSorts()
    {
        $collection = new RepositoryCollection(TestContact::class);
        $collection->addSort("Company.CompanyName");
        $collection->addSort("ContactID");

        $this->assertEquals("John", $collection[0]->Forename);
        $this->assertEquals("Babs", $collection[2]->Forename);
        $this->assertEquals("Jule", $collection[4]->Forename);
    }

    public function testDotNotationOnAggregates()
    {
        $collection = Company::all();
        $collection->addAggregateColumn(new Count("Contacts.All", "CountOfContacts"));
        $collection->addSort("CompanyID");

        $this->assertEquals(3, $collection[0]->CountOfContacts);
    }

    public function testComplicatedExample()
    {
        // This is a real life example that tries to find donations for contacts that have
        // qualifying gift aid declarations.
        $donation = new TestDonation();
        $donation->DonationDate = "now";
        $donation->save();

        $donation = new TestDonation();
        $donation->DonationDate = "now";
        $donation->save();

        $contact = new TestContact();
        $contact->Forename = "billy";
        $contact->save();

        $donation = new TestDonation();
        $donation->DonationDate = "now";
        $donation->ContactID = $contact->ContactID;
        $donation->save();
        
        $declaration = new TestDeclaration();
        $declaration->StartDate = "yesterday";
        $declaration->ContactID = $contact->ContactID;
        $declaration->save();

        $donations = TestDonation::all()
        ->intersectWith(
            TestDeclaration::all(),
            "ContactID",
            "ContactID",
            [
                "StartDate" => "DeclarationStartDate",
                "DonationID" => "DeclarationDonationID"
            ]
        )
        ->filter(
            new OrGroup([
		        new LessThan( "DeclarationStartDate", "@{DonationDate}" )
            ])
        );

        $this->assertCount(1, $donations);
        $this->assertEquals($contact->getUniqueIdentifier(), $donations[0]->ContactID);

        $contact = new TestContact();
        $contact->Forename = "robin";
        $contact->save();

        $donation = new TestDonation();
        $donation->DonationDate = "-3 days";
        $donation->ContactID = $contact->ContactID;
        $donation->save();

        $declaration = new TestDeclaration();
        $declaration->DonationID = $donation->DonationID;
        $declaration->ContactID = $contact->ContactID;
        $declaration->save();

        $donation = new TestDonation();
        $donation->DonationDate = "-2 days";
        $donation->ContactID = $contact->ContactID;
        $donation->save();

        $donations = TestDonation::all()
            ->intersectWith(
                TestDeclaration::all(),
                "ContactID",
                "ContactID",
                [
                    "StartDate" => "DeclarationStartDate",
                    "DonationID" => "DeclarationDonationID"
                ]
            )
            ->filter(
                new OrGroup([
                    new AndGroup([
                        new LessThan( "DeclarationStartDate", "@{DonationDate}" ),
                        new GreaterThan( "DeclarationStartDate", "0000-00-00" )
                    ]),
                    new Equals( "DeclarationDonationID", "@{DonationID}")
                ])
            );

        $this->assertCount(2, $donations);
        $this->assertEquals($contact->getUniqueIdentifier(), $donations[1]->ContactID);

        $contacts = TestContact::all()->
            intersectWith(
                TestDonation::all()
                    ->intersectWith(
                        TestDeclaration::all(),
                        "ContactID",
                        "ContactID",
                        [
                            "StartDate" => "DeclarationStartDate",
                            "DonationID" => "DeclarationDonationID"
                        ])
                    ->filter(
                        new AndGroup([
                            new OrGroup([
                                new LessThan( "DeclarationStartDate", "@{DonationDate}" ),
                                new Equals( "DeclarationDonationID", "@{DonationID}")
                            ]),
                        ])
                    )
                    ->addAggregateColumn(new Count("DonationID", "CountOfDonations")),
                    "ContactID",
                    "ContactID",
                    [ "CountOfDonations" ]
                )
            ->filter(new Equals("CountOfDonations", 2));

        $this->assertCount(1, $contacts);
        $this->assertEquals($contact->getUniqueIdentifier(), $donations[1]->ContactID);

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

        $contact = new TestContact();
        $contact->Forename = "John";
        $contact->CompanyID = 1;
        $contact->save();

        $contact = new TestContact();
        $contact->Forename = "Mary";
        $contact->CompanyID = 2;
        $contact->save();

        $contact = new TestContact();
        $contact->Forename = "Jule";
        $contact->CompanyID = 3;
        $contact->save();

        $contact = new TestContact();
        $contact->Forename = "John";
        $contact->CompanyID = 1;
        $contact->save();

        $contact = new TestContact();
        $contact->Forename = "Babs";
        $contact->CompanyID = 1;
        $contact->save();
    }
}