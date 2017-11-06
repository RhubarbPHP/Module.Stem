<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Interfaces\CheckExpiredModelInterface;

class TestExpiredUser extends User implements CheckExpiredModelInterface
{
    public function hasModelExpired()
    {
        return true;
    }
}
