<?php
/*
 * Suspended while validation is in flux

namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\Modelling\UnitTesting\User;
use Gcd\Core\UnitTesting\CoreTestCase;

class ValidationTest extends CoreTestCase
{
	public function testValidationGetsLabel()
	{
		$equalTo = new EqualTo( "Username", "abc" );

		$this->assertEquals( "Username", $equalTo->label );

		$equalTo = new EqualTo( "EarTagNumber", "abc" );

		$this->assertEquals( "Ear Tag Number", $equalTo->label );
	}

	public function testValidationCanBeInverted()
	{
		$equalTo = new EqualTo( "Username", "abc" );
		$notEqualTo = $equalTo->Invert();

		$user = new User();
		$user->Username = "def";

		$this->assertTrue( $notEqualTo->Validate( $user ) );

		$user->Username = "abc";
		$this->setExpectedException( "Gcd\Core\Modelling\Exceptions\ValidationErrorException" );
		$notEqualTo->Validate( $user );
	}
}

*/