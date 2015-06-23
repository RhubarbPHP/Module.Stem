<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class ModelUnitTestCase extends RhubarbTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        SolutionSchema::registerSchema("MySchema", "Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema");
    }
}