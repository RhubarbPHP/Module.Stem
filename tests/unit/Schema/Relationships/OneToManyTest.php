<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Relationships;

use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\UnitTestingSolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class OneToManyTest extends ModelUnitTestCase
{
    public function testOneToMany()
    {
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        $company = new Company();
        $company->getRepository()->clearObjectCache();
        $company->CompanyName = "Test Company";
        $company->save();

        $user = new User();
        $user->getRepository()->clearObjectCache();
        $user->Username = "jdoe";
        $user->Password = "asdfasdf";
        $user->Active = 1;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $user = new User();
        $user->Username = "msmith";
        $user->Password = "";
        $user->Active = 1;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $user = new User();
        $user->Username = "inactive dude";
        $user->Password = "";
        $user->Active = 0;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $oneToMany = new OneToMany(
            "Unused",
            "Company",
            "CompanyID",
            "UnitTestUser"
        );

        $list = $oneToMany->fetchFor($company);

        $this->assertCount(2, $list);
        $this->assertEquals("msmith", $list[1]->Username);
    }
}