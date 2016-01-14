<?php

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Models\Validation\EqualToModelProperty;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

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
