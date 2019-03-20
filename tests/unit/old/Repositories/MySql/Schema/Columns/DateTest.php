<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class DateTest extends MySqlTestCase
{
    public function testRepositoryGetsDateFormat()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->InceptionDate = "2012-01-01";
        $company->save();

        $params = MySql::getPreviousParameters();

        $this->assertContains("2012-01-01", $params["InceptionDate"]);

        $company->reload();

        $this->assertEquals("2012-01-01", $company->InceptionDate->format("Y-m-d"));

        $company->InceptionDate = "2011-01-01";
        $company->save();

        $company->reload();

        $this->assertEquals("2011-01-01", $company->InceptionDate->format("Y-m-d"));
    }
}