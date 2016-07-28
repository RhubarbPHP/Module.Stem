<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Tests\unit\Fixtures\Category;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\CompanyCategory;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class CollectionPropertyMatchesTest extends ModelUnitTestCase
{
    public function testAppendingCreatesRowInModel()
    {
        $companyCategory = new CompanyCategory();
        $companyCategory->getRepository()->clearObjectCache();

        $company = new Company();
        $company->CompanyName = "GCD";
        $company->save();

        $category = new Category();
        $category->CategoryName = "AppendTest";
        $category->save();

        $company->Categories->append($category);

        $categories = $company->Categories;
        $categories->count();
        $this->assertCount(1, $categories );

        $this->assertEquals($company->CompanyID, $companyCategory->getRepository()->cachedObjectData[1]["CompanyID"]);
        $this->assertEquals($category->CategoryID, $companyCategory->getRepository()->cachedObjectData[1]["CategoryID"]);
    }
}
