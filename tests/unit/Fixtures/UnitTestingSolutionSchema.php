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
        $this->addModel("TestContact", TestContact::class);
        $this->addModel("UnitTestUser", User::class);
        $this->addModel("TestDeclaration", TestDeclaration::class);
        $this->addModel("TestDonation", TestDonation::class);
    }

    public function defineRelationships()
    {
        $this->declareOneToManyRelationships(
            [
                "Company" =>
                    [
                        "Users" => "UnitTestUser.CompanyID",
                        "TestContacts" => "TestContact.CompanyID:ExampleRelationshipName",
                        "Contacts" => "TestContact.CompanyID",
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