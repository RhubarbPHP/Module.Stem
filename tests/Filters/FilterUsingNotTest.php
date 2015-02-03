<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 *            Tests the NOT filter.
 */
class FilterUsingNotTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
	/**
	 * @var Collection
	 */
	private $list;

	protected function setUp()
	{
		unset( $this->list );

		parent::setUp();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->getRepository()->clearObjectCache();
		$example->Forename = "John";
		$example->Surname = "Joe";
		$example->DateOfBirth = "1990-01-01";
		$example->ContactID = 1;
		$example->save();

		$example->Forename = "John";
		$example->Surname = "Johnson";
		$example->DateOfBirth = "1988-01-01";
		$example->ContactID = 2;
		$example->save();

		$example->Forename = "John";
		$example->Surname = "Luc";
		$example->DateOfBirth = "1990-01-01";
		$example->ContactID = 3;
		$example->save();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "Mary";
		$example->Surname = "Smithe";
		$example->DateOfBirth = "1980-06-09";
		$example->ContactID = 4;
		$example->save();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "Tom";
		$example->Surname = "Thumb";
		$example->DateOfBirth = "1976-05-09";
		$example->ContactID = 5;
		$example->save();

		$this->list = new Collection( "\Rhubarb\Stem\Tests\Fixtures\Example" );
	}

	function testFiltersSimple()
	{
		$filter = new \Rhubarb\Stem\Filters\Contains( "Forename", "jo" );
		$this->list->Not( $filter );
		$this->assertCount( 2, $this->list );
		$this->assertContains( "Mary", $this->list[ 0 ]->Forename );
	}

	function testFiltersWithGroup()
	{
		$filterGroup = new \Rhubarb\Stem\Filters\Group( "And" );
		$filterGroup->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Johnson", true )
		);
		$this->list->Not( $filterGroup );
		$this->assertCount( 4, $this->list );
		$this->assertContains( "Joe", $this->list[ 0 ]->Surname );
	}

	function testFiltersWithGroupedGroup()
	{
		$filterGroup1 = new \Rhubarb\Stem\Filters\Group( "And" );
		$filterGroup1->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Jo", true )
		);

		$filterGroup2 = new \Rhubarb\Stem\Filters\Group( "Or" );
		$filterGroup2->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Luc", true ),
			new \Rhubarb\Stem\Filters\LessThan( "DateOfBirth", "1980-01-01", true )
		);

		$filterGroup = new \Rhubarb\Stem\Filters\Group( "Or" );
		$filterGroup->addFilters(
			$filterGroup1,
			$filterGroup2
		);

		$this->list->Not( $filterGroup );
		$this->assertCount( 1, $this->list );
		$this->assertContains( "Smithe", $this->list[ 0 ]->Surname );
	}
}