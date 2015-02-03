<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 * Tests the NOT filter.
 */
class NotTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
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

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "James";
		$example->Surname = "Higgins";
		$example->DateOfBirth = "1996-05-09";
		$example->ContactID = 6;
		$example->save();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "John";
		$example->Surname = "Higgins";
		$example->DateOfBirth = "1996-05-09";
		$example->ContactID = 7;
		$example->save();

		$this->list = new Collection( "\Rhubarb\Stem\Tests\Fixtures\Example" );
	}

	function testFiltersSimple()
	{
		$notFilter = new \Rhubarb\Stem\Filters\Not( new \Rhubarb\Stem\Filters\Contains( "Forename", "jo" ) );
		$this->list->filter( $notFilter );
		$this->assertCount( 3, $this->list );
		$this->assertContains( "Mary", $this->list[ 0 ]->Forename );
	}

	function testFiltersWithGroup()
	{
		$filterGroup = new \Rhubarb\Stem\Filters\Group( "And" );
		$filterGroup->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Johnson", true )
		);
		$notFilter = new \Rhubarb\Stem\Filters\Not( $filterGroup );
		$this->list->filter( $notFilter );
		$this->assertCount(6, $this->list );
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

		$notFilter = new \Rhubarb\Stem\Filters\Not( $filterGroup );
		$this->list->filter( $notFilter );
		$this->assertCount( 3, $this->list );
		$this->assertContains( "Smithe", $this->list[ 0 ]->Surname );
	}

	function testXOR()
	{
		$filterOne = new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true );
		$filterTwo = new \Rhubarb\Stem\Filters\Contains( "Surname", "Jo", true );

		$filterAnd = new \Rhubarb\Stem\Filters\Group( "And" );
		$filterAnd->addFilters(
			$filterOne,
			$filterTwo
		);

		$filterOr = new \Rhubarb\Stem\Filters\Group( "Or" );
		$filterOr->addFilters(
			$filterOne,
			$filterTwo
		);

		$filterNotAnd = new \Rhubarb\Stem\Filters\Not( $filterAnd );

		$filterXor = new \Rhubarb\Stem\Filters\Group( "And" );
		$filterXor->addFilters(
			$filterNotAnd,
			$filterOr
		);
		$this->list->filter( $filterXor );
		$this->assertCount( 2, $this->list );
		$this->assertContains( "Luc", $this->list[ 0 ]->Surname );
	}
}