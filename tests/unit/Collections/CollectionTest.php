<?php

namespace Rhubarb\Stem\Tests\unit\Collections;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;

class CollectionTest extends RhubarbTestCase
{
    public function testBatchUpdate()
    {
        $a = new Company();
        $a->CompanyName = "a";
        $a->Active = true;
        $a->save();

        $b = new Company();
        $b->CompanyName = "b";
        $b->Active = true;
        $b->save();

        $c = new Company();
        $c->CompanyName = "c";
        $c->Active = true;
        $c->save();

        $companies = Company::find()->batchUpdate(
            [
                "CompanyName" => "d"
            ]
        );

        foreach ($companies as $company) {
            $this->assertEquals("d", $company->CompanyName);
        }
    }
}