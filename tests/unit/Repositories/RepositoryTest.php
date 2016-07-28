<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Aggregates\Sum;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\ModelException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

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

    public function testAggregatesOnComputedColumnsInAnIntersection()
    {
        $company1 = new Company();
        $company1->getRepository()->clearObjectCache();
        $company1->CompanyName = "1";
        $company1->save();

        $company2 = new Company();
        $company2->CompanyName = "2";
        $company2->save();

        $user1 = new User();
        $user1->Wage = 100;
        $company1->Users->append($user1);

        $user2 = new User();
        $user2->Wage = 200;
        $company1->Users->append($user2);

        $user3 = new User();
        $user3->Wage = 300;
        $company2->Users->append($user3);

        $user4 = new User();
        $user4->Wage = 400;
        $company2->Users->append($user4);

        $companies = new RepositoryCollection(Company::class);
        $companies->addAggregateColumn(new Sum("Users.BigWage"));

        $results = [];

        foreach ($companies as $company) {
            $results[] = $company->SumOfUsersBigWage;
        }

        $this->assertEquals([3000, 7000], $results);
    }
}