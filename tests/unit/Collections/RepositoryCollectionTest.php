<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Crown\DateTime\RhubarbDate;
use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\ArrayCollection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Between;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\TestDeclaration;
use Rhubarb\Stem\Tests\unit\Fixtures\TestDonation;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

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
        $collection->intersectWith(Company::find(new Equals("CompanyID", 2)), "CompanyID", "CompanyID",
            ["Balance" => "CompanyBalance"]);

        $this->assertEquals(2, $collection[0]->CompanyBalance);
    }

    public function testAggregates()
    {
        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts"))
                ->addGroup("CompanyID"),
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
                ->addAggregateColumn(new Sum("CompanyID"))
                ->addGroup("CompanyID"),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts", "SumOfCompanyID"]);

        $this->assertCount(3, $collection);
        $this->assertEquals(2, $collection[1]->SumOfCompanyID);
        $this->assertEquals(3, $collection[0]->SumOfCompanyID);

        $collection = Company::all();
        $collection->intersectWith(
            TestContact::all()
                ->addAggregateColumn(new Count("Contacts"))
                ->addGroup("CompanyID"),
            "CompanyID",
            "CompanyID",
            ["CountOfContacts"]);
        $collection->filter(new Equals("CountOfContacts", 3));

        $this->assertCount(1, $collection);
        $this->assertEquals(3, $collection[0]->CountOfContacts);
    }

    public function testGroupingRemovesItemsFromResultsSet()
    {
        $companies = [];
        foreach (TestContact::all() as $contact) {
            if (isset($companies[$contact->CompanyID])) {
                $companies[$contact->CompanyID] += $contact->CompanyID;
            } else {
                $companies[$contact->CompanyID] = $contact->CompanyID;
            }
        }

        $results = TestContact::all()->addAggregateColumn(new Sum('CompanyID'))->addGroup("CompanyID");
        self::assertCount(count($companies), $results);

        foreach ($results as $result) {
            self::assertEquals($companies[$result->CompanyID], $result->SumOfCompanyID);
        }
    }

    public function testLimits()
    {
        $collection = Company::all();
        $collection->setRange(0, 2);

        $x = 0;
        foreach ($collection as $item) {
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

        foreach ($contacts as $contact) {
            $contrivedArray[] = $contact;
        }

        $collection->intersectWith(
            (new ArrayCollection("TestContact", $contrivedArray))
                ->addAggregateColumn(new Count("Contacts"))
                ->addGroup("CompanyID"),
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
                ->addAggregateColumn(new Count("Contacts"))
                ->addGroup("CompanyID"),
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

        $contact = $billy = new TestContact();
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
                    new LessThan("DeclarationStartDate", "@{DonationDate}")
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
                        new LessThan("DeclarationStartDate", "@{DonationDate}"),
                        new GreaterThan("DeclarationStartDate", "0000-00-00")
                    ]),
                    new Equals("DeclarationDonationID", "@{DonationID}")
                ])
            );

        $this->assertCount(2, $donations);
        $this->assertEquals($contact->getUniqueIdentifier(), $donations[1]->ContactID);

        $donation = new TestDonation();
        $donation->DonationDate = "now";
        $donation->ContactID = $billy->ContactID;
        $donation->save();


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
                    new OrGroup([
                        new AndGroup([
                            new LessThan("DeclarationStartDate", "@{DonationDate}"),
                            new GreaterThan("DeclarationStartDate", "0000-00-00")
                        ]),
                        new Equals("DeclarationDonationID", "@{DonationID}")
                    ])
                )
                ->addAggregateColumn(new Count("DonationID", "CountOfDonations")),
            "ContactID",
            "ContactID",
            ["CountOfDonations"]
        )
            ->filter(new Equals("CountOfDonations", 2));

        $this->assertCount(1, $contacts);
        $this->assertEquals($billy->getUniqueIdentifier(), $contacts[0]->ContactID);

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
        $contact->DateOfBirth = "now";
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


    public function createTestContact()
    {

        $contact = new TestContact();
        $contact->Forename = substr(md5(rand()), 0, 4);
        $contact->Surname = substr(md5(rand()), 0, 6);

        $contact->Postcode = substr(md5(rand()), 0, 7);
        $contact->AddressLine1 = substr(md5(rand()), 0, 15);

        $contact->save();
        return $contact;
    }

    public function createDeclarationForDonation($donationId, $contactId)
    {
        $declaration = new TestDeclaration();
        $declaration->ContactID = $contactId;
        $declaration->DonationID = $donationId;
        $declaration->EndDate = new RhubarbDate("0000-00-00");
        $declaration->StartDate = new RhubarbDate("0000-00-00");
        $declaration->save();

        return $declaration;
    }

    public static function createDeclarationForDateRange($startDate, $endDate, $contactId)
    {
        $declaration = new TestDeclaration();
        $declaration->ContactID = $contactId;
        $declaration->DonationID = 0;
        $declaration->StartDate = $startDate;
        $declaration->EndDate = $endDate;
        $declaration->save();

        return $declaration;
    }

    /*
     * In this test there are:
     *
     * 13 Contacts
     *  $contact1 ... $contact13
     * 19 Donations
     *  $donation1 ... $donation17
     * 17 Declarations
     *
     * Donations 1-15, 18 and 19 have been made in 2016, so should be found in the search.
     * Donations 16 and 17 have been made in 2015, so should not be found.
     *
     * Donations 1,2,3,4,5,6,7,8,11,14 all have declarations specific to them - and so should be applicable
     *
     * Contacts 2, 3 and 9 have also made declarations for a range of dates
     *  $contact9 has cancelled their declaration - so their donation isn't applicable
     *  $contact2 has an open ended declaration starting from 2016-01-01 - which can be used for either
     *      of his donations ($donation2 and $donation12)
     *  $contact3 has a closed ended declaration ranging from 2015-01-01 to 2016-12-31
     *      which is applicable for both of his donations ($donation3 and $donation13),
     *      however $donation3 won't be found as it's 2015
     *  $contact11 has a declaration ending on the day of his donation (so their donation $donation14 should be found)
     *  $contact12 has a declaration ending the day before his donation (so not applicable)
     */
    public function testLargeComplexIntersectionTest()
    {
        // 10 Contacts
        $contact1 = $this->createTestContact();
        $contact2 = $this->createTestContact();
        $contact3 = $this->createTestContact();
        $contact4 = $this->createTestContact();
        $contact5 = $this->createTestContact();
        $contact6 = $this->createTestContact();
        $contact7 = $this->createTestContact();
        $contact8 = $this->createTestContact();
        $contact9 = $this->createTestContact();
        $contact10 = $this->createTestContact();
        $contact11 = $this->createTestContact();
        $contact12 = $this->createTestContact();
        $contact13 = $this->createTestContact();

        // $contact4 has no Postcode
        $contact4->Postcode = "";
        $contact4->save();
        // $contact5 has no AddressLine1
        $contact5->AddressLine1 = "";
        $contact5->save();
        // $contact6 has no Surname
        $contact6->Surname = "";
        $contact6->save();
        // $contact13 has no required stuffs
        $contact13->Postcode = "";
        $contact13->AddressLine1 = "";
        $contact13->Surname = "";
        $contact13->save();

        $allContacts = TestContact::all();
        $this->assertEquals(18, sizeof($allContacts), "There should be 18 contacts");


        $may2016 = new RhubarbDate("2016-05-05");

        // 19 Donations
        $donation1 = new TestDonation();
        $donation1->ContactID = $contact1->ContactID;
        $donation1->Amount = 10;
        $donation1->DonationDate = $may2016;
        $donation1->save();

        $donation2 = new TestDonation();
        $donation2->ContactID = $contact2->ContactID;
        $donation2->Amount = 10;
        $donation2->DonationDate = $may2016;
        $donation2->save();

        $donation3 = new TestDonation();
        $donation3->ContactID = $contact3->ContactID;
        $donation3->Amount = 10;
        $donation3->DonationDate = $may2016;
        $donation3->save();

        $donation4 = new TestDonation();
        $donation4->ContactID = $contact4->ContactID;
        $donation4->Amount = 10;
        $donation4->DonationDate = $may2016;
        $donation4->save();

        $donation5 = new TestDonation();
        $donation5->ContactID = $contact5->ContactID;
        $donation5->Amount = 10;
        $donation5->DonationDate = $may2016;
        $donation5->save();

        $donation6 = new TestDonation();
        $donation6->ContactID = $contact6->ContactID;
        $donation6->Amount = 10;
        $donation6->DonationDate = $may2016;
        $donation6->save();

        $donation7 = new TestDonation();
        $donation7->ContactID = $contact7->ContactID;
        $donation7->Amount = 10;
        $donation7->DonationDate = $may2016;
        $donation7->save();

        $donation8 = new TestDonation();
        $donation8->ContactID = $contact8->ContactID;
        $donation8->Amount = 10;
        $donation8->DonationDate = $may2016;
        $donation8->save();

        $donation9 = new TestDonation();
        $donation9->ContactID = $contact9->ContactID;
        $donation9->Amount = 10;
        $donation9->DonationDate = $may2016;
        $donation9->save();

        $donation10 = new TestDonation();
        $donation10->ContactID = $contact10->ContactID;
        $donation10->Amount = 10;
        $donation10->DonationDate = $may2016;
        $donation10->save();

        $donation11 = new TestDonation();
        $donation11->ContactID = $contact1->ContactID;
        $donation11->Amount = 10;
        $donation11->DonationDate = $may2016;
        $donation11->save();

        $donation12 = new TestDonation();
        $donation12->ContactID = $contact2->ContactID;
        $donation12->Amount = 10;
        $donation12->DonationDate = $may2016;
        $donation12->save();

        $donation13 = new TestDonation();
        $donation13->ContactID = $contact3->ContactID;
        $donation13->Amount = 10;
        $donation13->DonationDate = $may2016;
        $donation13->save();

        $donation14 = new TestDonation();
        $donation14->ContactID = $contact11->ContactID;
        $donation14->Amount = 10;
        $donation14->DonationDate = $may2016;
        $donation14->save();

        $donation15 = new TestDonation();
        $donation15->ContactID = $contact12->ContactID;
        $donation15->Amount = 10;
        $donation15->DonationDate = $may2016;
        $donation15->save();

        // 2 in 2015 (Outside date range)
        $donation16 = new TestDonation();
        $donation16->ContactID = $contact3->ContactID;
        $donation16->Amount = 10;
        $donation16->DonationDate = new RhubarbDate("2015-05-05");
        $donation16->save();

        $donation17 = new TestDonation();
        $donation17->ContactID = $contact5->ContactID;
        $donation17->Amount = 10;
        $donation17->DonationDate = new RhubarbDate("2015-05-05");
        $donation17->save();

        $donation18 = new TestDonation();
        $donation18->ContactID = $contact13->ContactID;
        $donation18->Amount = 10;
        $donation18->DonationDate = $may2016;
        $donation18->save();

        $donation19 = new TestDonation();
        $donation19->ContactID = $contact13->ContactID;
        $donation19->Amount = 10;
        $donation19->DonationDate = $may2016;
        $donation19->save();


        $allDonations = TestDonation::all();
        $this->assertEquals(19, sizeof($allDonations), "There should be 19 donations");


        // 10 Donation specific declarations
        // Notes:
        //  $donation1 and $donation11 are by the same contact - $contact1
        //  $donation16 was in 2015
        $this->createDeclarationForDonation($donation1->DonationID, $donation1->ContactID);
        $this->createDeclarationForDonation($donation2->DonationID, $donation2->ContactID);
        $this->createDeclarationForDonation($donation3->DonationID, $donation3->ContactID);
        $this->createDeclarationForDonation($donation4->DonationID, $donation4->ContactID);
        $this->createDeclarationForDonation($donation5->DonationID, $donation5->ContactID);
        $this->createDeclarationForDonation($donation6->DonationID, $donation6->ContactID);
        $this->createDeclarationForDonation($donation7->DonationID, $donation7->ContactID);
        $this->createDeclarationForDonation($donation8->DonationID, $donation8->ContactID);
        $this->createDeclarationForDonation($donation11->DonationID, $donation11->ContactID);
        $this->createDeclarationForDonation($donation16->DonationID, $donation16->ContactID);
        $this->createDeclarationForDonation($donation18->DonationID, $donation18->ContactID);

        // 5 Date range declarations
        // Open ended declaration for Contact 2 - Covering from 2016 onwards
        $this->createDeclarationForDateRange(new RhubarbDate("2016-01-01"), new RhubarbDate("0000-00-00"),
            $contact2->ContactID);

        // Close ended declaration for Contact 3 - Covering all of 2015 and 2016
        $this->createDeclarationForDateRange(new RhubarbDate("2015-01-01"), new RhubarbDate("2016-12-31"),
            $contact3->ContactID);

        //Close ended declaration ending on the day of the donation
        $this->createDeclarationForDateRange(new RhubarbDate("2015-01-01"), new RhubarbDate("2016-05-05"),
            $contact11->ContactID);

        //Close ended declaration ending on the day of the donation
        $this->createDeclarationForDateRange(new RhubarbDate("2015-01-01"), new RhubarbDate("2016-05-04"),
            $contact12->ContactID);

        // Cancelled close ended declaration for Contact 9 - Covering all of 2015 and 2016
        $declaration = $this->createDeclarationForDateRange(new RhubarbDate("2016-01-01"),
            new RhubarbDate("2016-12-31"),
            $contact9->ContactID);
        $declaration->Cancelled = true;
        $declaration->save();

        // Open ended declaration for Contact 13 - Covering from 2016 onwards
        $this->createDeclarationForDateRange(new RhubarbDate("2016-01-01"), new RhubarbDate("0000-00-00"),
            $contact13->ContactID);


        $allDeclarations = TestDeclaration::all();
        $this->assertEquals(17, sizeof($allDeclarations), "There should be 17 declarations");

        // Find all of our donations within the date range
        $startDate = new RhubarbDate("2016-01-01");
        $endDate = new RhubarbDate("2016-12-31");

        $donations = TestDonation::find(new AndGroup([
            new Between("DonationDate", $startDate, $endDate)
        ]));


        $this->assertEquals(17, sizeof($donations), "We should find 17 donations between $startDate and $endDate
        (\$donation16 and \$donation17 shouldn't be found as they lie outside the date range)");


        /*
         * Find all the Declarations that aren't cancelled. Intersect this with $donations where either:
         *  [ The DeclarationDonationID equals the DonationID of one of the donations ]
         * or
         *
         * [
         *      The DeclarationStartDate is less than the Date the donation was made
         *  AND
         *      ( The DeclarationEndDate is after the Date the donation was made
         *          OR
         *      The DeclarationEndDate is open ended )
         *  AND
         *      The DeclarationDonationID isn't set
         *  ]
         *
         * I.e. Remove any donation that we can't find a suitable donation for
         */
        $donations->intersectWith(TestDeclaration::find(
            new Equals("Cancelled", false)
        ),
            "ContactID",
            "ContactID",
            [
                "StartDate" => "DeclarationStartDate",
                "EndDate" => "DeclarationEndDate",
                "DonationID" => "DeclarationDonationID"
            ]
        )->filter(
            new OrGroup(
                new Equals("DeclarationDonationID", "@{DonationID}"),
                new AndGroup(
                    new LessThan("DeclarationStartDate", "@{DonationDate}", true),
                    new OrGroup(
                        new GreaterThan("DeclarationEndDate", "@{DonationDate}", true),
                        new OrGroup(
                            new Equals("DeclarationEndDate", new RhubarbDate("0000-00-00")),
                            new Equals("DeclarationEndDate", "0000-00-00")
                        )
                    ),
                    new Equals("DeclarationDonationID", 0)
                )
            )
        );

        $this->assertEquals(14, sizeof($donations),
            "We should filter out 3 donations who don't have applicable declarations
            (\$donation9, \$donation10 and \$donation15 removed)");


        /*
         * Intersect this with All Contacts that have a name, address and postcode - requirements
         */


        $donations->intersectWith(
            TestContact::all(),
            "ContactID",
            "ContactID",
            [
                "Surname",
                "Postcode",
                "AddressLine1"
            ]
        )->filter(
            new Not(new Equals("Surname", "")),
            new Not(new Equals("AddressLine1", "")),
            new Not(new Equals("Postcode", ""))
        );

        $this->assertEquals(9, sizeof($donations),
            "5 more donations should be filtered out as they don't have all the required contact information 
            (\$donation4, \$donation5, \$donation6, \$donation18 and \$donation19 removed)");


        // Just a final check to ensure the 9 found are the ones we were expecting
        $string = "";
        foreach ($donations as $donation) {
            $string .= "[DonationID:" . $donation->DonationID . "]";
        }

        $this->assertNotFalse(strpos($string, "[DonationID:1]"), "\$donation1 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:2]"), "\$donation2 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:3]"), "\$donation3 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:7]"), "\$donation7 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:8]"), "\$donation8 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:11]"), "\$donation11 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:12]"), "\$donation12 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:13]"), "\$donation13 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:14]"), "\$donation14 should be found");


        $donations = TestDonation::find(new AndGroup([
            new Between("DonationDate", $startDate, $endDate)
        ]))->
        intersectWith(TestDeclaration::find(
            new Equals("Cancelled", false)
        ),
            "ContactID",
            "ContactID",
            [
                "StartDate" => "DeclarationStartDate",
                "EndDate" => "DeclarationEndDate",
                "DonationID" => "DeclarationDonationID"
            ]
        )->filter(
            new OrGroup(
                new Equals("DeclarationDonationID", "@{DonationID}"),
                new AndGroup(
                    new LessThan("DeclarationStartDate", "@{DonationDate}", true),
                    new OrGroup(
                        new GreaterThan("DeclarationEndDate", "@{DonationDate}", true),
                        new OrGroup(
                            new Equals("DeclarationEndDate", new RhubarbDate("0000-00-00")),
                            new Equals("DeclarationEndDate", "0000-00-00")
                        )
                    ),
                    new Equals("DeclarationDonationID", 0)
                )
            )
        )->intersectWith(TestContact::find(
            new AndGroup([
                new Not(new Equals("Surname", "")),
                new Not(new Equals("AddressLine1", "")),
                new Not(new Equals("Postcode", ""))
            ])),
            "ContactID",
            "ContactID"
        );

        $this->assertEquals(9, sizeof($donations),
            "We should have 9 by filtering then intersecting (above was intersect then filter)");

        $string = "";
        foreach ($donations as $donation) {
            $string .= "[DonationID:" . $donation->DonationID . "]";
        }

        $this->assertNotFalse(strpos($string, "[DonationID:1]"), "\$donation1 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:2]"), "\$donation2 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:3]"), "\$donation3 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:7]"), "\$donation7 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:8]"), "\$donation8 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:11]"), "\$donation11 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:12]"), "\$donation12 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:13]"), "\$donation13 should be found");
        $this->assertNotFalse(strpos($string, "[DonationID:14]"), "\$donation14 should be found");
    }

    public function testAttachOn()
    {
        MySql::executeStatement("TRUNCATE TABLE tblCompany");
        MySql::executeStatement("TRUNCATE TABLE tblUser");

        $user1 = new User();
        $user2 = new User();
        $user3 = new User();

        $user1->Forename = "Cow";
        $user2->Forename = "Goat";
        $user3->Forename = "Penguin";

        $user1->Surname = "Cowington";
        $user2->Surname = "Goatington";
        $user3->Surname = "Soldier";

        $user1->Active = true;
        $user2->Active = true;
        $user3->Active = true;

        $company1 = new Company();
        $company1->CompanyName = "Animal Farm";
        $company1->Active = true;
        $company1->Balance = 100000;
        $company1->save();

        $company2 = new Company();
        $company2->CompanyName = "Airforce";
        $company2->Active = true;
        $company2->Balance = 400000;
        $company2->save();

        $user1->CompanyID = 0;
        $user2->CompanyID = $company1->CompanyID;
        $user3->CompanyID = $company2->CompanyID;

        $user1->save();
        $user2->save();
        $user3->save();


        $users = User::all();

        $companies = Company::all();

        $this->assertEquals(3, sizeof($users), "There should be three users");

        $users->joinWith($companies,
            "CompanyID",
            "CompanyID",
            [
                "CompanyName",
                "Balance"
            ], true);

        $this->assertEquals(3, sizeof($users), "After performing the attachment, we should still have three records");

        $this->assertEquals("", $users[0]->CompanyName, "\$User1 should still be there, but have no company attached");
        $this->assertEquals("Animal Farm", $users[1]->CompanyName, "\$User2 should have had the correct company attached to them");
        $this->assertEquals("Airforce", $users[2]->CompanyName, "\$User3 should have had the correct company attached to them");
    }
}