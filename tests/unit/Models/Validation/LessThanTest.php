<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 09/09/2013
 * Time: 11:34
 */

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Models\Validation\LessThan;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class LessThanTest extends RhubarbTestCase
{
    function testValidation()
    {
        $user = new User();
        $lessThan = new LessThan("UserID", 1000);

        $user->UserID = 2000;

        try {
            $lessThan->validate($user);
            $this->fail("Validation should have failed");
        } catch (ModelConsistencyValidationException $er) {
        }

        $user->UserID = "500";
        $this->assertTrue($lessThan->validate($user));
    }

    function testEqualToValidation()
    {
        $user = new User();
        $lessThanOrEqual = new LessThan("UserID", 1000, true);

        $user->UserID = "1000";

        $this->assertTrue($lessThanOrEqual->validate($user));
    }
}
