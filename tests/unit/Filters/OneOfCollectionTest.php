<?php

namespace unit\Filters;

use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Filters\OneOfCollection;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;

class OneOfCollectionTest extends ModelUnitTestCase
{
    public function testFilterOneOfCollection()
    {
        $company1 = new Company();
        $company1->CompanyName = 'c1';
        $company1->save();

        $company2 = new Company();
        $company2->CompanyName = 'c2';
        $company2->save();

        $company3 = new Company();
        $company3->CompanyName = 'c3';
        $company3->save();

        $contact1 = new TestContact();
        $contact1->CompanyID = $company1->getUniqueIdentifier();
        $contact1->save();

        $contact2 = new TestContact();
        $contact2->CompanyID = $company2->getUniqueIdentifier();
        $contact2->save();

        $contact3 = new TestContact();
        $contact3->CompanyID = $company3->getUniqueIdentifier();
        $contact3->save();

        // This is a bad example of the usage of OneOfCollection - this operation would be best as an intersection.
        // this filter is designed for use when you need an "or'd intersection".
        $results = TestContact::find(new OneOfCollection('CompanyID', Company::find(new OneOf('CompanyName', ['c1', 'c2']))));

        self::assertCount(2, $results);
    }
}
