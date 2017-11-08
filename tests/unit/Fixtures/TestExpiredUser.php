<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Interfaces\ValidateLoginModelInterface;

class TestExpiredUser extends User implements ValidateLoginModelInterface
{
    public function isModelExpired()
    {
        return true;
    }

    public function isModelDisabled()
    {
        return false;
    }
}
