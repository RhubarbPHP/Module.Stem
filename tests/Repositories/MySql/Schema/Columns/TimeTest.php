<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;
use Rhubarb\Stem\Tests\Fixtures\Company;

/** 
 * 
 *
 * @package Rhubarb\Stem\Repositories\MySql\Schema\Columns
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class TimeTest extends MySqlTestCase
{
	public function testRepositoryGetsTimeFormat()
	{
		$company = new Company();
		$company->CompanyName = "GCD";
		$company->KnockOffTime = "17:01:02";
		$company->save();

		$params = MySql::getPreviousParameters();

		$this->assertContains( "17:01:02", $params[ "KnockOffTime" ] );

		$company->reload();

		$this->assertEquals( "2000-01-01 17:01:02", $company->KnockOffTime->format( "Y-m-d H:i:s" ) );
	}
} 