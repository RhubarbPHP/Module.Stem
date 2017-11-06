<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

class TestExpiredLoginProvider extends ModelLoginProvider
{
    public function __construct()
    {
        parent::__construct(TestExpiredUser::class, "Username", "Password", "Active");
    }
}
