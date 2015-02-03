<?php

namespace Rhubarb\Stem\Tests\Aggregates;

use Rhubarb\Stem\Aggregates\Count;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class CountTest extends RhubarbTestCase
{
	public function testCount()
	{
		$company = new Company();
		$company->CompanyName = "a";
		$company->Active = true;
		$company->save();

		$company = new Company();
		$company->CompanyName = "b";
		$company->Active = true;
		$company->save();

		$company = new Company();
		$company->CompanyName = "c";
		$company->Active = true;
		$company->save();

		$company = new Company();
		$company->CompanyName = "d";
		$company->Active = true;
		$company->save();

		$collection = Company::find();

		list( $companies ) = $collection->calculateAggregates( new Count( "CompanyName" ) );

		$this->assertEquals( 4, $companies );
	}
}
