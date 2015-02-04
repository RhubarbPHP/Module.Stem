<?php

namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\UnitTesting\CoreTestCase;

class HasValueTest extends CoreTestCase
{
	public function testValidation()
	{
		$validation = new HasValue( "" );

		$this->assertTrue( $validation->test( "abc" ) );
		$this->assertTrue( $validation->test( 123 ) );

		$this->assertFalse( $validation->test( "" ) );
		$this->assertFalse( $validation->test( 0 ) );
		$this->assertFalse( $validation->test( null ) );
	}
}
