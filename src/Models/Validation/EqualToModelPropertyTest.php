<?php


namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\Modelling\Exceptions\ModelConsistencyValidationException;
use Gcd\Core\Modelling\UnitTesting\User;
use Gcd\Core\UnitTesting\CoreTestCase;


class EqualToModelPropertyTest extends CoreTestCase
{
    public function testValidation()
    {
        $user = new User();

        $equals = new EqualToModelProperty("Username", "Email");

        $user->Username = "def";
        $user->Email = "abc";

        try {
            $equals->validate($user);
            $this->fail("Validation should have failed");
        } catch (ModelConsistencyValidationException $er) {

        }


        $user->Username = "abc";
        $this->assertTrue($equals->validate($user));
    }
}
