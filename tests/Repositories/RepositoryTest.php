<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Tests\Fixtures\Example;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

class RepositoryTest extends ModelUnitTestCase
{
    public function testDefaultRepositoryIsOffline()
    {
        $repository = Repository::getNewDefaultRepository(new Example());

        $this->assertInstanceOf("\Rhubarb\Stem\Repositories\Offline\Offline", $repository);
    }

    public function testDefaultRepositoryCanBeChanged()
    {
        Repository::setDefaultRepositoryClassName("\Rhubarb\Stem\Repositories\MySql\MySql");

        $repository = Repository::getNewDefaultRepository(new Example());

        $this->assertInstanceOf("\Rhubarb\Stem\Repositories\MySql\MySql", $repository);

        // Also check that non extant repositories throw an exception.
        $this->setExpectedException("\Rhubarb\Stem\Exceptions\ModelException");

        Repository::setDefaultRepositoryClassName("\Rhubarb\Stem\Repositories\Fictional\Fictional");

        // Reset to the normal so we don't upset other unit tests.
        Repository::setDefaultRepositoryClassName("\Rhubarb\Stem\Repositories\Offline\Offline");
    }

    public function testHydrationOfNonExtantObjectThrowsException()
    {
        $offline = new Offline(new Example());

        $this->setExpectedException("Rhubarb\Stem\Exceptions\RecordNotFoundException");

        // Load the example data object with a silly identifier that doesn't exist.
        $offline->hydrateObject(new \Rhubarb\Stem\Tests\Fixtures\Example(), 10);
    }
}
