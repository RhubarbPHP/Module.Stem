<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\EndsWith;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class EndsWithTest extends ModelUnitTestCase
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
        $example->Forename = "John";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    public function testFiltersCaseInsensitive()
    {
        $filter = new EndsWith("Forename", "ry", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new EndsWith("Forename", "RY", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new EndsWith("Forename", "Ma", false);
        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }

    public function testFiltersCaseSensitive()
    {
        $filter = new EndsWith("Forename", "ry", true);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new EndsWith("Forename", "RY", true);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }
}