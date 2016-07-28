<?php

/**
 * Copyright (c) 2016 RhubarbPHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Filters\ColumnIntersectsCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class IntersectsCollectionTest extends ModelUnitTestCase
{
    public function testFilter()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $contact = new TestContact();
        $contact->Forename = "Andrew";
        $contact->save();

        $company->Contacts->append($contact);

        $contact = new TestContact();
        $contact->Forename = "Boris";
        $contact->save();

        $company->Contacts->append($contact);

        $company = new Company();
        $company->CompanyName = "GCD 2";
        $company->save();

        $contact = new TestContact();
        $contact->Forename = "Andrew";
        $contact->save();

        $company->Contacts->append($contact);

        $company = new Company();
        $company->CompanyName = "GCD 3";
        $company->save();

        $contact = new TestContact();
        $contact->Forename = "John";
        $contact->save();

        $company->Contacts->append($contact);

        $collection = Company::find();
        $collection->filter(new ColumnIntersectsCollection("CompanyID", TestContact::find(new Equals("Forename", "Andrew"))));

        $this->assertCount(2, $collection);

        $collection = Company::find();
        $collection->filter(new ColumnIntersectsCollection("CompanyID", TestContact::find(new Equals("Forename", "Boris"))));

        $this->assertCount(1, $collection);
    }
}