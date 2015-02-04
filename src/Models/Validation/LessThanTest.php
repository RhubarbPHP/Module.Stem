<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 09/09/2013
 * Time: 11:34
 */

namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\Modelling\Exceptions\ModelConsistencyValidationException;
use Gcd\Core\Modelling\UnitTesting\User;
use Gcd\Core\UnitTesting\CoreTestCase;

class LessThanTest extends CoreTestCase
{
	function testValidation()
	{
		$user = new User();
		$lessThan = new LessThan( "UserID", 1000 );

		$user->UserID = 2000;

		try
		{
			$lessThan->validate( $user );
			$this->fail( "Validation should have failed" );
		}
		catch( ModelConsistencyValidationException $er )
		{
		}

		$user->UserID = "500";
		$this->assertTrue( $lessThan->validate( $user ) );
	}

	function testEqualToValidation()
	{
		$user = new User();
		$lessThanOrEqual = new LessThan( "UserID", 1000, true );

		$user->UserID = "1000";

		$this->assertTrue( $lessThanOrEqual->validate( $user ) );
	}
}