<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;

class AverageTest extends MySqlTestCase
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
		$example->CompanyName = "b";
		$example->Balance = 5;
		$example->save();

		$example = new Company();
		$example->CompanyName = "c";
		$example->Balance = 3;
		$example->save();
	}

	public function testSumIsCalculatedOnRepository()
	{
		$examples = new Collection( "Company" );

		list( $sumTotal ) = $examples->calculateAggregates(
			[ new MySqlAverage( "Balance" ) ] );

		$this->assertEquals( 3, $sumTotal );

		$lastStatement = MySql::GetPreviousStatement( false );

		$this->assertContains( "AVG( `Balance` ) AS `AverageOfBalance`", $lastStatement );
	}
}
 