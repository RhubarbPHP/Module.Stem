<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class ModelUnitTestCase extends RhubarbTestCase
{
    /**
     * @var UnitTestingSolutionSchema
     */
    protected $db;

    protected function setUp()
    {
        parent::setUp();

        $this->db = new UnitTestingSolutionSchema(Container::instance(MySql::class));
    }
}
