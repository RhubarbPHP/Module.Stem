<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Tests\Fixtures\Example;

/**
 * Data filter used to keep all records with a variable which is exactly equal to a particular variable.
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class EqualsTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
	/**
	 * @var Collection
	 */
	private $list;

	protected function setUp()
	{
		parent::setUp();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->getRepository()->clearObjectCache();
		$example->Forename = "John";
		$example->save();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "Mary";
		$example->save();

		$example = new \Rhubarb\Stem\Tests\Fixtures\Example();
		$example->Forename = "Tom";
		$example->Surname = "Thumb";
		$example->save();

		$this->list = new Collection( "\Rhubarb\Stem\Tests\Fixtures\Example" );
	}

	public function testFiltersMatchingRows()
	{
		$filter = new \Rhubarb\Stem\Filters\Equals( "Forename", "Tom" );

		$this->list->filter( $filter );

		$this->assertCount( 1, $this->list );
		$this->assertEquals( "Thumb", $this->list[0]->Surname );
	}

	public function testSetFilterValue()
	{
		$filter = new \Rhubarb\Stem\Filters\Equals( "CompanyID", 1 );
		$model = new Example();

		$filter->setFilterValuesOnModel( $model );

		$this->assertEquals( 1, $model->CompanyID );
	}
}
