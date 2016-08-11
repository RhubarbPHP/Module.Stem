<?php

namespace Rhubarb\Stem\Tests\unit\Filters;

use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class FilterTest extends ModelUnitTestCase
{
    public function testCanFilterOnRelatedModelProperties()
    {
        $gcd = new Company();
        $gcd->CompanyName = "GCD";
        $gcd->save();

        $widgetCo = new Company();
        $widgetCo->CompanyName = "Widgets";
        $widgetCo->save();

        $example = new User();
        $example->Username = "a";

        $users = $widgetCo->Users;
        $users->append($example);

        $example = new User();
        $example->Username = "b";

        $gcd->Users->append($example);

        $example = new User();
        $example->Username = "c";

        $widgetCo->Users->append($example);

        $example = new User();
        $example->Username = "d";

        $gcd->Users->append($example);

        $list = new RepositoryCollection(User::class);
        $list->filter(new Equals("Company.CompanyName", "GCD"));

        $this->assertCount(2, $list);
        $this->assertEquals("b", $list[0]->Username);
        $this->assertEquals("d", $list[1]->Username);
    }
}
