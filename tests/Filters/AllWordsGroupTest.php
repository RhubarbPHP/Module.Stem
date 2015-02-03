<?php
/**
 * Created by PhpStorm.
 * User: nsmyth
 * Date: 27/03/14
 * Time: 08:53
 */

namespace Rhubarb\Stem\Tests\Filters;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Filters\AllWordsGroup;

class AllWordsGroupTest extends RhubarbTestCase
{
	public function testFilterCreation()
	{
		$group = new AllWordsGroup( [ "Forename", "Surname" ], "Mister Blobby" );

		$filters = $group->getFilters();

		$this->assertCount( 2, $filters );

		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\OrGroup', $filters[0] );
		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\OrGroup', $filters[1] );

		$misterFilters = $filters[0]->GetFilters();
		$this->assertCount( 2, $misterFilters );
		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\Contains', $misterFilters[0] );
		$this->assertEquals( 'Forename', $misterFilters[0]->GetColumnName() );
		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\Contains', $misterFilters[1] );
		$this->assertEquals( 'Surname', $misterFilters[1]->GetColumnName() );

		$blobbyFilters = $filters[1]->GetFilters();
		$this->assertCount( 2, $blobbyFilters );
		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\Contains', $blobbyFilters[0] );
		$this->assertEquals( 'Forename', $blobbyFilters[0]->GetColumnName() );
		$this->assertInstanceOf( 'Rhubarb\Stem\Filters\Contains', $blobbyFilters[1] );
		$this->assertEquals( 'Surname', $blobbyFilters[1]->GetColumnName() );
	}
}
