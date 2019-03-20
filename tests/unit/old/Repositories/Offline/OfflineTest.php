<?php

namespace Repositories\Offline;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Repositories\Offline\Offline;

class OfflineTest extends RhubarbTestCase
{
    public function testNonExistantIdentifierThrows()
    {
        $offline = new Offline();
        $this->expectException(RecordNotFoundException::class);
        $offline->getEntityByIdentifier(10);
    }

}
