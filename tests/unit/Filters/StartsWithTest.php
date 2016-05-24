<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\StartsWith;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;

class StartsWithTest extends ModelUnitTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        unset($this->list);

        parent::setUp();

        $example = new Example();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->save();

        $example = new Example();
        $example->Forename = "Mary";
        $example->save();

        $example = new Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->save();

        $this->list = new RepositoryCollection(Example::class);
    }

    public function testFiltersCaseInsensitive()
    {
        $filter = new StartsWith("Forename", "jo", false);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new StartsWith("Forename", "Jo", false);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new StartsWith("Forename", "hn", false);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }

    public function testFiltersCaseSensitive()
    {
        $filter = new StartsWith("Forename", "Jo", true);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new StartsWith("Forename", "hn", true);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }
}