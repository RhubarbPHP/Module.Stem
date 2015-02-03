<?php

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */

namespace Rhubarb\Stem\Tests\Schema\Relationships;


use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\Fixtures\User;

class OneToOneTest extends ModelUnitTestCase
{
	public function testOneToOne()
	{
		SolutionSchema::registerSchema( "MySchema", "Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema" );

		$company = new Company();
		$company->CompanyName = "Test Company";
		$company->save();

		$user = new User();
		$user->Username = "jdoe";
		$user->Password = "asdfasdf";
		$user->Active = 1;
		$user->CompanyID = $company->CompanyID;
		$user->save();

		$oneToOne = new OneToOne(
			"Unused",
			"User",
			"CompanyID",
			"Company",
			"CompanyID"
		);

		$result = $oneToOne->fetchFor( $user );

		$this->assertEquals( "Test Company", $result->CompanyName );
	}
}