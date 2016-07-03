<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Exceptions\ModelException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class RepositoryTest extends ModelUnitTestCase
{
    public function testDefaultRepositoryIsOffline()
    {
        $repository = Repository::getNewDefaultRepository(new TestContact());

        $this->assertInstanceOf(Offline::class, $repository);
    }

    public function testDefaultRepositoryCanBeChanged()
    {
        Repository::setDefaultRepositoryClassName(MySql::class);

        $repository = Repository::getNewDefaultRepository(new TestContact());

        $this->assertInstanceOf(MySql::class, $repository);

        // Also check that non extant repositories throw an exception.
        $this->setExpectedException(ModelException::class);

        Repository::setDefaultRepositoryClassName('\Rhubarb\Stem\Repositories\Fictional\Fictional');

        // Reset to the normal so we don't upset other unit tests.
        Repository::setDefaultRepositoryClassName(Offline::class);
    }

    public function testHydrationOfNonExtantObjectThrowsException()
    {
        $offline = new Offline(new TestContact());

        $this->setExpectedException(RecordNotFoundException::class);

        // Load the example data object with a silly identifier that doesn't exist.
        $offline->hydrateObject(new TestContact(), 10);
    }
}