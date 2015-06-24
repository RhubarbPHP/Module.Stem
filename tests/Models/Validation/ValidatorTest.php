<?php
/*
 * Suspended while validation is in flux

namespace Rhubarb\Stem\Tests\Models\Validation;

use Rhubarb\Stem\Tests\Fixtures\User;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class ValidatorTest extends RhubarbTestCase
{
	public function testValidatorThrowsException()
	{
		$validator = new Validator();
		$validator->validations[] = new EqualTo( "Username", "abc" );

		$user = new User();
		$user->Username = "abc";

		$validator->Validate( $user );

		$user->Username = "def";

		$this->setExpectedException( "Gcd\Core\Modelling\Exceptions\ValidationErrorException" );

		$validator->Validate( $user );
	}

	public function testValidateOneMode()
	{
		$validator = new Validator( "", Validator::VALIDATE_ONE );
		$validator->validations[] = new EqualTo( "Username", "abc" );
		$validator->validations[] = new EqualTo( "Forename", "john" );

		$user = new User();
		$user->Username = "abc";
		$user->Forename = "Chris";

		// This should work okay as one validates
		$validator->Validate( $user );

		$user->Username = "def";
		$user->Forename = "john";

		// ditto
		$validator->Validate( $user );

		$user->Username = "abc";
		$user->Forename = "john";

		// ditto
		$validator->Validate( $user );

		$user->Username = "123";
		$user->Forename = "456";

		// Now it should fail

		$this->setExpectedException( "Gcd\Core\Modelling\Exceptions\ValidationErrorException" );

		$validator->Validate( $user );
	}
}
*/