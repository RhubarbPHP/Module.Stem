<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 */
class OneOfTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
    /**
     * @var Collection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "Pugh";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Pugh";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Barney";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "McGrew";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Cuthbert";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Dibble";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Grub";
        $example->save();

        $this->list = new Collection("\Rhubarb\Stem\Tests\Fixtures\Example");
    }

    public function testFilters()
    {

        $filter = new \Rhubarb\Stem\Filters\OneOf("Forename", ["Cuthbert", "Dibble", "Grub", "Pugh"]);

        $this->list->filter($filter);
        $this->assertCount(5, $this->list);
        $this->assertContains("Pugh", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\OneOf("Forename", ["Cuthbert", "Dibble", "Grub"]);
        $this->list->filter($filter);
        $this->assertCount(3, $this->list);
        $this->assertContains("Cuthbert", $this->list[0]->Forename);
    }


}