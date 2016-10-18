<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Logging\PhpLog;
use Rhubarb\Stem\Filters\JsonContains;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\User;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class MysqlJsonContainsTest extends MySqlTestCase
{
    protected function setUp()
    {
        parent::setUp();

        MySql::executeStatement("TRUNCATE TABLE tblUser");
    }

    public function testJsonColumnContains()
    {
        $total = User::find(new JsonContains('ProfileData', 'foundyou'))->count();

        $user1 = new User();
        $user1->Active = true;
        $user1->ProfileData = ['test', 'moretest', 'evenmore'];
        $user1->save();

        $user2 = new User();
        $user2->Active = true;
        $user2->ProfileData = ['foundyou', 'test', 'supertests'];
        $user2->save();

        $new = User::find(new JsonContains('ProfileData', 'foundyou'))->count();

        self::assertEquals($total + 1, $new);
    }
}
