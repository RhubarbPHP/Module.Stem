<?php

namespace Rhubarb\Stem\Tests\Aggregates;

use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\Example;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class SumTest extends RhubarbTestCase
{
	protected function setUp()
	{
		parent::setUp();

		$example = new Example();
		$example->getRepository()->clearObjectCache();

		$example = new Example();
		$example->Forename = "a";
		$example->CompanyID = 1;
		$example->save();

		$example = new Example();
		$example->Forename = "b";
		$example->CompanyID = 2;
		$example->save();

		$example = new Example();
		$example->Forename = "c";
		$example->CompanyID = 3;
		$example->save();
	}

	public function testSum()
	{
		$examples = new Collection( "Example" );

		list( $sumTotal ) = $examples->calculateAggregates(
			[ new Sum( "CompanyID" ) ] );

		$this->assertEquals( 6, $sumTotal );
	}
}
