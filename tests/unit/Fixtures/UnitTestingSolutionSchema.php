<?php

namespace Rhubarb\Stem\Tests\unit\Fixtures;

use Rhubarb\Stem\Schema\SolutionSchema;

class UnitTestingSolutionSchema extends SolutionSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->addModel("Company", Company::class);
        $this->addModel("Category", Category::class);
        $this->addModel("CompanyCategory", CompanyCategory::class);
        $this->addModel("Example", Example::class);
        $this->addModel("UnitTestUser", User::class);
    }

    public function defineRelationships()
    {
        $this->declareOneToManyRelationships(
            [
                "Company" =>
                    [
                        "Users" => "UnitTestUser.CompanyID",
                        "TestContacts" => "Example.CompanyID:ExampleRelationshipName",
                        "Contacts" => "Example.CompanyID",
                    ],
            ]
        );

        $this->declareManyToManyRelationships(
            [
                "Company" =>
                    [
                        "Categories" => "CompanyCategory.CompanyID_CategoryID.Category:Companies"
                    ]
            ]
        );
    }
}