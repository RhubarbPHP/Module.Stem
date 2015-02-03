<?php

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Crown\Tests\RhubarbTestCase;

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

		SolutionSchema::registerSchema( "MySchema", "Rhubarb\Stem\Tests\Fixtures\UnitTestingSolutionSchema" );
	}
}