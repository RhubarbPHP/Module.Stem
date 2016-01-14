<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Relationships;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\Category;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\CompanyCategory;

class ManyToManyTest extends RhubarbTestCase
{
    public function testManyToMany()
    {
        // UnitTestingSolutionSchema sets up a many to many relationship between company and category
        $company1 = new Company();
        $company2 = new Company();
        $company3 = new Company();
        $company1->getRepository()->clearObjectCache();

        $companyCategory = new CompanyCategory();
        $companyCategory->getRepository()->clearObjectCache();

        $category1 = new Category();
        $category2 = new Category();
        $category1->getRepository()->clearObjectCache();

        $company1->CompanyName = "GCD";
        $company1->save();

        $company2->CompanyName = "UTV";
        $company2->save();

        $company3->CompanyName = 'Inactive';
        $company3->Active = 0;
        $company3->save();

        $category1->CategoryName = "Fruit";
        $category1->save();

        $category2->CategoreName = "Apples";
        $category2->save();

        $companyCategory->CategoryID = $category1->CategoryID;
        $companyCategory->CompanyID = $company1->CompanyID;
        $companyCategory->save();

        $companyCategory = new CompanyCategory();
        $companyCategory->CategoryID = $category1->CategoryID;
        $companyCategory->CompanyID = $company2->CompanyID;
        $companyCategory->save();

        $companyCategory = new CompanyCategory();
        $companyCategory->CategoryID = $category2->CategoryID;
        $companyCategory->CompanyID = $company2->CompanyID;
        $companyCategory->save();

        $companyCategory = new CompanyCategory();
        $companyCategory->CategoryID = $category1->CategoryID;
        $companyCategory->CompanyID = $company3->CompanyID;
        $companyCategory->save();

        // At this point GCD is in Fruit, while UTV is in Fruit and Apples.
        $company1 = new Company($company1->CompanyID);

        $this->assertCount(1, $company1->Categories);
        $this->assertCount(2, $company2->Categories);
        $this->assertCount(2, $category1->Companies);
        $this->assertCount(1, $category2->Companies);

        $this->assertEquals("UTV", $category2->Companies[0]->CompanyName);
    }
}
