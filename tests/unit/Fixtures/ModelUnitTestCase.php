<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Schema\SolutionSchema;

class ModelUnitTestCase extends RhubarbTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);
    }
}