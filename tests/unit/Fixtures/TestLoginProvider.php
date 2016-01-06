<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

class TestLoginProvider extends ModelLoginProvider
{
    public function __construct()
    {
        parent::__construct(User::class, "Username", "Password", "Active");
    }
}