<?php

namespace Schema;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Tests\unit\Fixtures\Schemas\AccountSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class ModelSchemaTest extends ModelUnitTestCase
{
    public function testFindGetsCollection()
    {
        $list = $this->db->getSolutionSchema('Account') ::find();

        verify($list)->isInstanceOf(Collection::class);
        verify($list->getModelSchema())->isInstanceOf(AccountSchema::class);
    }
}
