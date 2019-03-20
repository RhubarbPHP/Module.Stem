<?php
namespace Rhubarb\Stem\Tests\unit\Aggregates;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Aggregates\Min;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
class MinTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $example = new TestContact();
        $example->getRepository()->clearObjectCache();

        $example = new TestContact();
        $example->Forename = "a";
        $example->CompanyID = 1;
        $example->save();

        $example = new TestContact();
        $example->Forename = "b";
        $example->CompanyID = 2;
        $example->save();

        $example = new TestContact();
        $example->Forename = "c";
        $example->CompanyID = 3;
        $example->save();
    }

    public function testMin()
    {
        $examples = TestContact::find();

        list($min) = $examples->calculateAggregates(
            [new Min("CompanyID")]
        );

        $this->assertEquals(1, $min, "the minimum should have been 1, from 'a'");
    }
}