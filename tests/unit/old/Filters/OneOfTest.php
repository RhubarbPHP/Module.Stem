<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class OneOfTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new TestContact();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "Pugh";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Pugh";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Barney";
        $example->save();

        $example = new TestContact();
        $example->Forename = "McGrew";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Cuthbert";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Dibble";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Grub";
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    public function testFilters()
    {
        $filter = new OneOf("Forename", ["Cuthbert", "Dibble", "Grub", "Pugh"]);

        $this->list->filter($filter);
        $this->assertCount(5, $this->list);
        $this->assertContains("Pugh", $this->list[0]->Forename);

        $filter = new OneOf("Forename", ["Cuthbert", "Dibble", "Grub"]);
        $this->list->filter($filter);
        $this->assertCount(3, $this->list);
        $this->assertContains("Cuthbert", $this->list[0]->Forename);
    }
}