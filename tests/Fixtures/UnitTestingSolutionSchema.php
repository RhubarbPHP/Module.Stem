<?php

namespace Rhubarb\Stem\Tests\Fixtures;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
use Rhubarb\Stem\Schema\SolutionSchema;

class UnitTestingSolutionSchema extends SolutionSchema
{
	public function __construct()
	{
		parent::__construct();

		$this->addModel( "Company", "Rhubarb\Stem\Tests\Fixtures\Company" );
		$this->addModel( "Category", "Rhubarb\Stem\Tests\Fixtures\Category" );
		$this->addModel( "CompanyCategory", "Rhubarb\Stem\Tests\Fixtures\CompanyCategory" );
		$this->addModel( "Example", "Rhubarb\Stem\Tests\Fixtures\Example" );
		$this->addModel( "UnitTestUser", "Rhubarb\Stem\Tests\Fixtures\User" );
	}

	public function defineRelationships()
	{
		$this->declareOneToManyRelationships(
			[
				"Company" =>
				[
					"Users" => "UnitTestUser.CompanyID",
				]
			]
		);

		$this->declareOneToManyRelationships(
			[
				"Company.CompanyID" =>
				[
					"TestContacts" => "Example.CompanyID:ExampleRelationshipName",
					"Contacts" => "Example.CompanyID",
				],
			]
		);

		$this->declareManyToManyRelationships
		(
			[
				"Company" =>
				[
					"Categories" => "CompanyCategory.CompanyID_CategoryID.Category:Companies"
				]
			]
		);
	}
}