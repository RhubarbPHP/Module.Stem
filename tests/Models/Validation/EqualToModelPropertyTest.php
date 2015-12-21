<?php

namespace Rhubarb\Stem\Tests\Models\Validation;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Models\Validation\EqualToModelProperty;
use Rhubarb\Stem\Tests\Fixtures\User;

class EqualToModelPropertyTest extends RhubarbTestCase
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