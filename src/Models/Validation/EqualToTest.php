<?php

namespace Rhubarb\Stem\Models\Validation;

use Gcd\Core\Modelling\Exceptions\ModelConsistencyValidationException;
use Gcd\Core\Modelling\UnitTesting\User;
use Gcd\Core\UnitTesting\CoreTestCase;

class EqualToTest extends CoreTestCase
{
    public function testValidation()
    {
        $user = new User();

        $equals = new EqualTo("Username", "abc");

        $user->Username = "def";

        try {
            $equals->validate($user);
            $this->fail("Validation should have failed");
        } catch (ModelConsistencyValidationException $er) {

        }


        $user->Username = "abc";
        $this->assertTrue($equals->validate($user));

        $user->Username = 234;
        $equals = new EqualTo("Username", 123);

        try {
            $equals->validate($user);
            $this->fail("Validation should have failed");
        } catch (ModelConsistencyValidationException $er) {
        }

        $user->Username = 123;
        $this->assertTrue($equals->validate($user));
    }
}
