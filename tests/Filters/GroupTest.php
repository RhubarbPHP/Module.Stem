<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Filters\Group;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Tests\Fixtures\Example;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 */
class GroupTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
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

	public function testFiltersAnd()
	{
		$filterGroup = new Group( "And" );
		$filterGroup->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Johnson", true )
		);
		$this->list->Filter( $filterGroup );
		$this->assertCount( 1, $this->list );
		$this->assertContains( "Johnson", $this->list[ 0 ]->Surname );
	}

	public function testFiltersOr()
	{
		$filterGroup = new Group( "Or" );
		$filterGroup->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Smithe", true )
		);
		$this->list->Filter( $filterGroup );
		$this->assertCount( 4, $this->list );
		$this->assertContains( "Smithe", $this->list[ 3 ]->Surname );
	}

	//filter Group with a group inside for recursive banter
	public function testFiltersGrouped()
	{
		$filterGroup1 = new Group( "And" );
		$filterGroup1->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Forename", "Jo", true ),
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Jo", true )
		);

		$filterGroup2 = new Group( "Or" );
		$filterGroup2->addFilters(
			new \Rhubarb\Stem\Filters\Contains( "Surname", "Luc", true ),
			new \Rhubarb\Stem\Filters\LessThan( "DateOfBirth", "1980-01-01", true )
		);

		$filterGroup = new Group( "Or" );
		$filterGroup->addFilters(
			$filterGroup1,
			$filterGroup2
		);
		$this->list->Filter( $filterGroup );
		$this->assertCount( 4, $this->list );
		$this->assertContains( "Joe", $this->list[ 0 ]->Surname );
	}

	public function testFilterSetsModelValues()
	{
		$subGroup = new Group( "And" );
		$subGroup->addFilters
			(
				new Equals( "Forename", "Andrew" ),
				new GreaterThan( "DateOfBirth", 18 )
			);

		$andGroup = new Group( "And" );
		$andGroup->addFilters
			(
				new Equals( "CompanyID", 1 ),
				new Equals( "Surname", "Cuthbert" ),
				$subGroup
			);

		$orGroup = new Group( "Or" );
		$orGroup->addFilters
			(
				new Equals( "CompanyID", 1 ),
				new Equals( "Surname", "Cuthbert" ),
				$subGroup
			);

		$model = new Example();
		$andGroup->setFilterValuesOnModel( $model );

		$this->assertEquals( 1, $model->CompanyID );
		$this->assertEquals( "Cuthbert", $model->Surname );
		$this->assertEquals( "Andrew", $model->Forename );

		$model = new Example();
		$orGroup->setFilterValuesOnModel( $model );

		$this->assertNotEquals( 1, $model->CompanyID );
		$this->assertNotEquals( "Cuthbert", $model->Surname );
		$this->assertNotEquals( "Andrew", $model->Forename );
	}
}