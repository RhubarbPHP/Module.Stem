<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 */
class EndsWithTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
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
        $example->Forename = "John";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Mary";
        $example->save();

        $example = new \Rhubarb\Stem\Tests\Fixtures\Example();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->save();

        $this->list = new Collection("\Rhubarb\Stem\Tests\Fixtures\Example");
    }

    public function testFiltersCaseInsensitive()
    {

        $filter = new \Rhubarb\Stem\Filters\EndsWith("Forename", "ry", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\EndsWith("Forename", "RY", false);
        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\EndsWith("Forename", "Ma", false);
        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }

    public function testFiltersCaseSensitive()
    {

        $filter = new \Rhubarb\Stem\Filters\EndsWith("Forename", "ry", true);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("Mary", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\EndsWith("Forename", "RY", true);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }
}