<?php

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Models\Validation\EqualTo;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class EqualToTest extends RhubarbTestCase
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