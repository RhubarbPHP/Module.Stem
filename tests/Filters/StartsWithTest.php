<?php

namespace Gcd\Tests;

use Rhubarb\Stem\Collections\Collection;

/**
 *
 * @author    rkilfedder
 * @copyright GCD Technologies 2012
 */
class StartsWithTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
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

        $filter = new \Rhubarb\Stem\Filters\StartsWith("Forename", "jo", false);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\StartsWith("Forename", "Jo", false);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\StartsWith("Forename", "hn", false);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);

    }

    public function testFiltersCaseSensitive()
    {

        $filter = new \Rhubarb\Stem\Filters\StartsWith("Forename", "Jo", true);

        $this->list->filter($filter);
        $this->assertCount(1, $this->list);
        $this->assertContains("John", $this->list[0]->Forename);

        $filter = new \Rhubarb\Stem\Filters\StartsWith("Forename", "hn", true);

        $this->list->filter($filter);
        $this->assertCount(0, $this->list);
    }
}