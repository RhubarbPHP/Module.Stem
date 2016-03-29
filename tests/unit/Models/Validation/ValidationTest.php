<?php
/*
 * Suspended while validation is in flux

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Stem\Tests\unit\Fixtures\User;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;

class ValidationTest extends RhubarbTestCase
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
		$notEqualTo = $equalTo->invert();

		$user = new User();
		$user->Username = "def";

		$this->assertTrue( $notEqualTo->validate( $user ) );

		$user->Username = "abc";
		$this->setExpectedException( "Gcd\Core\Modelling\Exceptions\ValidationErrorException" );
		$notEqualTo->validate( $user );
	}
}

*/
