<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Relationships;

use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelUnitTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\UnitTestingSolutionSchema;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class OneToOneTest extends ModelUnitTestCase
{
    public function testOneToOne()
    {
        SolutionSchema::registerSchema("MySchema", UnitTestingSolutionSchema::class);

        $company = new Company();
        $company->CompanyName = "Test Company";
        $company->save();

        $user = new User();
        $user->Username = "jdoe";
        $user->Password = "asdfasdf";
        $user->Active = 1;
        $user->CompanyID = $company->CompanyID;
        $user->save();

        $oneToOne = new OneToOne(
            "Unused",
            "User",
            "CompanyID",
            "Company",
            "CompanyID"
        );

        $result = $oneToOne->fetchFor($user);

        $this->assertEquals("Test Company", $result->CompanyName);
    }
}