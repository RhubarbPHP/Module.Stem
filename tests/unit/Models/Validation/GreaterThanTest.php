<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 10/09/2013
 * Time: 08:54
 */

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Models\Validation\GreaterThan;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class GreaterThanTest extends RhubarbTestCase
{
    function testValidation()
    {
        $user = new User();
        $greaterThan = new GreaterThan("UserID", 1000);

        $user->UserID = 500;

        try {
            $greaterThan->validate($user);
            $this->fail("Validation should have failed");
        } catch (ModelConsistencyValidationException $er) {
        }

        $user->UserID = "2000";
        $this->assertTrue($greaterThan->validate($user));
    }

    function testEqualToValidation()
    {
        $user = new User();
        $greaterThanOrEqual = new GreaterThan("UserID", 1000, true);

        $user->UserID = "1000";

        $this->assertTrue($greaterThanOrEqual->validate($user));
    }
}
