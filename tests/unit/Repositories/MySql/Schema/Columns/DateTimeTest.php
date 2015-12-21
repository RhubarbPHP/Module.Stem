<?php

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class DateTimeTest extends MySqlTestCase
{
    public function testRepositoryGetsDateFormat()
    {
        $company = new Company();
        $company->CompanyName = "GCD";
        $company->LastUpdatedDate = "2012-01-01 10:01:02";
        $company->save();

        $params = MySql::getPreviousParameters();

        $this->assertContains("2012-01-01 10:01:02", $params["LastUpdatedDate"]);

        $company->reload();

        $this->assertEquals("2012-01-01 10:01:02", $company->LastUpdatedDate->format("Y-m-d H:i:s"));
    }
}