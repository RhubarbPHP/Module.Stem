<?php

namespace Rhubarb\Stem\Tests\Filters;

use Rhubarb\Stem\Tests\Fixtures\Category;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\CompanyCategory;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class CollectionPropertyMatchesTest extends RhubarbTestCase
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

		$company->Categories->Append( $category );

		$this->assertCount( 1, $company->Categories );

		$this->assertEquals( $company->CompanyID, $companyCategory->getRepository()->cachedObjectData[ 1 ][ "CompanyID" ] );
		$this->assertEquals( $category->CategoryID, $companyCategory->getRepository()->cachedObjectData[ 1 ][ "CategoryID" ] );

	}
}
