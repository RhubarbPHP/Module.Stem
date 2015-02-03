<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Repositories\MySql\MySql;
//use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;

class CountDistinctTest extends MySqlTestCase
{
	protected function setUp()
	{
		parent::setUp();

		MySql::executeStatement( "TRUNCATE TABLE tblCompany" );

		$example = new Company();
		$example->getRepository()->clearObjectCache();

		$example = new Company();
		$example->CompanyName = "a";
		$example->Balance = 1;
		$example->save();

		$example = new Company();
		$example->CompanyName = "a";
		$example->Balance = 2;
		$example->save();

		$example = new Company();
		$example->CompanyName = "b";
		$example->Balance = 3;
		$example->save();

		$example = new Company();
		$example->CompanyName = "b";
		$example->Balance = 4;
		$example->save();
	}

	public function testSumIsCalculatedOnRepository()
	{
		$examples = new Collection( "Company" );

		list( $sumTotal ) = $examples->calculateAggregates(
			[ new MySqlCountDistinct( "CompanyName" ) ] );

		$this->assertEquals( 2, $sumTotal );

		$lastStatement = MySql::GetPreviousStatement( false );

		$this->assertContains( "COUNT( DISTINCT `CompanyName` ) AS `DistinctCountOfCompanyName`", $lastStatement );
	}
}
 