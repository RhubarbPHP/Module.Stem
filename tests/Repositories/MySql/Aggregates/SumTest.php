<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\Example;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class SumTest extends MySqlTestCase
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
		$example->Balance = 2;
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
			[ new MySqlSum( "Balance" ) ] );

		$this->assertEquals( 6, $sumTotal );

		$lastStatement = MySql::GetPreviousStatement( false );

		$this->assertContains( "SUM( `Balance` ) AS `SumOfBalance`", $lastStatement );

		$examples = new Collection( "Company" );
		$examples->filter( new GreaterThan( "Balance", 1 ) );

		list( $sumTotal ) = $examples->calculateAggregates( new MySqlSum( "Balance" ) );

		$this->assertEquals( 5, $sumTotal );

		$lastStatement = MySql::GetPreviousStatement( false );

		$this->assertContains( "SUM( `Balance` ) AS `SumOfBalance`", $lastStatement );
		$this->assertContains( "WHERE `tblCompany`.`Balance` > ", $lastStatement );
	}
}
