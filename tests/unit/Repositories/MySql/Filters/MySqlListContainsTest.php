<?php

namespace Rhubarb\Stem\Tests\unit\Filters;


use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\ListContains;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\TestContact;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class MySqlListContainsTest extends MySqlTestCase
{
    /**
     * @var RepositoryCollection
     */
    private $list;

    protected function setUp()
    {
        parent::setUp();

        MySql::executeStatement("TRUNCATE TABLE tblContact");

        $example = new TestContact();
        $example->getRepository()->clearObjectCache();
        $example->Forename = "John";
        $example->AgesOfFriends = [21,24,15,43,12];
        $example->save();

        $example = new TestContact();
        $example->Forename = "Mary";
        $example->AgesOfFriends = [21,24,31,43,12];
        $example->save();

        $example = new TestContact();
        $example->Forename = "Tom";
        $example->Surname = "Thumb";
        $example->AgesOfFriends = [21,24,31,43,12];
        $example->save();

        $this->list = TestContact::find();
    }

    public function testFilterMatchesRows()
    {
        $this->assertCount(3, $this->list);

        $filter = new ListContains('AgesOfFriends', [15]);
        $this->list->replaceFilter($filter);

        $this->assertCount(1, $this->list);

        $filter = new ListContains('AgesOfFriends', [21,31]);
        $this->list->replaceFilter($filter);

        $this->assertCount(2, $this->list);

        $this->assertTrue($this->list->getFilter()->wasFilteredByRepository());
    }
}