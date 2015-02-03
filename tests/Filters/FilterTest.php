<?php

namespace Rhubarb\Stem\Tests\Filters;


use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\Example;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\Fixtures\User;

class FilterTest extends ModelUnitTestCase
{
	public function testCanFilterOnRelatedModelProperties()
	{
		$gcd = new Company();
		$gcd->CompanyName = "GCD";
		$gcd->save();

		$widgetCo = new Company();
		$widgetCo->CompanyName = "Widgets";
		$widgetCo->save();

		$example = new User();
		$example->Username = "a";

		$widgetCo->Users->Append( $example );

		$example = new User();
		$example->Username = "b";

		$gcd->Users->Append( $example );

		$example = new User();
		$example->Username = "c";

		$widgetCo->Users->Append( $example );

		$example = new User();
		$example->Username = "d";

		$gcd->Users->Append( $example );

		$list = new Collection( "Rhubarb\Stem\Tests\Fixtures\User" );
		$list->filter( new Equals( "Company.CompanyName", "GCD" ) );

		$this->assertCount( 2, $list );
		$this->assertEquals( "b", $list[0]->Username );
		$this->assertEquals( "d", $list[1]->Username );
	}
}
