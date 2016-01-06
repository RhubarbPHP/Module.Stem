<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class ModelUnitTestCase extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Repository::setDefaultRepositoryClassName(Offline::class);
        Model::deleteRepositories();
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);
    }
}