<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Family
 * Date: 30/09/13
 * Time: 21:49
 * To change this template use File | Settings | File Templates.
 */

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Stem\Tests\Fixtures\Example;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class BooleanTest extends RhubarbTestCase
{
	public function testDataTransforms()
	{
		$example = new Example();
		$example->KeyContact = 1;

		$this->assertTrue( $example->KeyContact );
	}
}
