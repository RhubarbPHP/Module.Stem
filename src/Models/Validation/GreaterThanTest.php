<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 10/09/2013
 * Time: 08:54
 */

namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\Modelling\Exceptions\ModelConsistencyValidationException;
use Gcd\Core\Modelling\UnitTesting\User;
use Gcd\Core\UnitTesting\CoreTestCase;

class GreaterThanTest extends CoreTestCase
{
	function testValidation()
	{
		$user = new User();
		$greaterThan = new GreaterThan( "UserID", 1000 );

		$user->UserID = 500;

		try
		{
			$greaterThan->validate( $user );
			$this->fail( "Validation should have failed" );
		}
		catch( ModelConsistencyValidationException $er )
		{
		}

		$user->UserID = "2000";
		$this->assertTrue( $greaterThan->validate( $user ) );
	}

	function testEqualToValidation()
	{
		$user = new User();
		$greaterThanOrEqual = new GreaterThan( "UserID", 1000, true );

		$user->UserID = "1000";

		$this->assertTrue( $greaterThanOrEqual->validate( $user ) );
	}
}
