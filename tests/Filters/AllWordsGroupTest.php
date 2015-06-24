<?php

namespace Rhubarb\Stem\Tests\Filters;

use Rhubarb\Stem\Filters\AllWordsGroup;
use Rhubarb\Stem\Filters\Contains;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

class AllWordsGroupTest extends ModelUnitTestCase
{
    public function testFilterCreation()
    {
        $group = new AllWordsGroup(["Forename", "Surname"], "Mister Blobby");

        $filters = $group->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf(OrGroup::class, $filters[0]);
        $this->assertInstanceOf(OrGroup::class, $filters[1]);

        $misterFilters = $filters[0]->GetFilters();
        $this->assertCount(2, $misterFilters);
        $this->assertInstanceOf(Contains::class, $misterFilters[0]);
        $this->assertEquals('Forename', $misterFilters[0]->GetColumnName());
        $this->assertInstanceOf(Contains::class, $misterFilters[1]);
        $this->assertEquals('Surname', $misterFilters[1]->GetColumnName());

        $blobbyFilters = $filters[1]->GetFilters();
        $this->assertCount(2, $blobbyFilters);
        $this->assertInstanceOf(Contains::class, $blobbyFilters[0]);
        $this->assertEquals('Forename', $blobbyFilters[0]->GetColumnName());
        $this->assertInstanceOf(Contains::class, $blobbyFilters[1]);
        $this->assertEquals('Surname', $blobbyFilters[1]->GetColumnName());
    }
}