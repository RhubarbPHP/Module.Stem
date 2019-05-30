<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Sorts;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Repositories\MySql\Sorts\MySqlRandom;

class MySqlRandomTest extends RhubarbTestCase
{
    public function testCustomSortSQL()
    {
        $this->assertEquals('RAND()', (new MySqlRandom())->getSqlExpression());
    }
}
