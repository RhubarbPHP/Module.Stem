<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\LessThan;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class LessThanTest extends ModelUnitTestCase
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
        $example->DateOfBirth = "1990-01-01";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->DateOfBirth = "1980-06-09";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Clancy";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $example = new TestContact();
        $example->Forename = "Clifford";
        $example->Surname = "Morris";
        $example->DateOfBirth = "1976-05-09";
        $example->save();

        $this->list = new RepositoryCollection(TestContact::class);
    }

    public function testFiltersDate()
    {
        $filter = new LessThan("DateOfBirth", "1979-01-01");

        $this->list->filter($filter);
        $this->assertCount(2, $this->list);
        $this->assertContains("Tom", $this->list[0]->Forename);
    }

    public function testFiltersAlpha()
    {
        $filter = new LessThan("Forename", "Mary", true);

        $this->list->filter($filter);
        $this->assertCount(3, $this->list);

        $filter = new LessThan("Forename", "Mary", false);
        $this->list->filter($filter);
        $this->assertCount(2, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);
    }

    public function testFiltersOnOtherColumn()
    {
        $filter = new LessThan("Forename", "@{Surname}", true);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertEquals("Morris", $this->list[0]->Surname);
    }
}