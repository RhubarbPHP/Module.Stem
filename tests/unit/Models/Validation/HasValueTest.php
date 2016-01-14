<?php

namespace Rhubarb\Stem\Tests\unit\Models\Validation;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Models\Validation\HasValue;

class HasValueTest extends RhubarbTestCase
{
    public function testValidation()
    {
        $validation = new HasValue("");

        $this->assertTrue($validation->test("abc"));
        $this->assertTrue($validation->test(123));

        $this->assertFalse($validation->test(""));
        $this->assertFalse($validation->test(0));
        $this->assertFalse($validation->test(null));
    }
}
