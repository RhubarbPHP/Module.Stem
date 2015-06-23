<?php

namespace Rhubarb\Stem\Tests\Fixtures;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class TestLoginProvider extends \Rhubarb\Stem\LoginProviders\ModelLoginProvider
{
    public function __construct()
    {
        parent::__construct("Rhubarb\Stem\Tests\Fixtures\User", "Username", "Password", "Active");
    }

}
