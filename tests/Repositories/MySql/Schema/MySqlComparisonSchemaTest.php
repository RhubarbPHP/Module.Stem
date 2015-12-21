<?php

namespace Rhubarb\Stem\Tests\Repositories\MySql\Schema;

use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlComparisonSchema;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Tests\Fixtures\Company;
use Rhubarb\Stem\Tests\Fixtures\User;
use Rhubarb\Stem\Tests\Repositories\MySql\MySqlTestCase;

class MySqlComparisonSchemaTest extends MySqlTestCase
{
    public function testSchemaCanBeCreatedFromTable()
    {
        MySql::executeStatement("DROP TABLE IF EXISTS tblTest");
        MySql::executeStatement(
            "CREATE TABLE `tblTest` (
                `ID` INT(10) NOT NULL AUTO_INCREMENT,
                `Nullable` INT(10) NULL DEFAULT NULL,
                `DefaultColumn` VARCHAR(50) NOT NULL DEFAULT 'Smith',
                `EnumColumn` ENUM('Open','Complete', 'Awaiting Feedback') NOT NULL,
                `Name` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`ID`),
                INDEX `Name` (`Name`)
            )
            COLLATE='latin1_swedish_ci'
            ENGINE=InnoDB;"
        );

        $comparisonSchema = MySqlComparisonSchema::fromTable("tblTest");

        $this->assertEquals([
            "ID" => "`ID` int(10) NOT NULL AUTO_INCREMENT",
            "Nullable" => "`Nullable` int(10) DEFAULT NULL",
            "DefaultColumn" => "`DefaultColumn` varchar(50) NOT NULL DEFAULT 'Smith'",
            "EnumColumn" => "`EnumColumn` enum('Open','Complete','Awaiting Feedback') NOT NULL",
            "Name" => "`Name` varchar(50) NOT NULL",
        ], $comparisonSchema->columns);

        $this->assertEquals(
            [
                "PRIMARY KEY (`ID`)",
                "KEY `Name` (`Name`)"
            ], $comparisonSchema->indexes
        );
    }

    public function testSchemaCanBeCreatedFromMySqlSchema()
    {
        $user = new User();
        $schema = $user->getSchema();

        $comparisonSchema = MySqlComparisonSchema::fromMySqlSchema($schema);

        $this->assertEquals(
            [
                "UserID" => "`UserID` int(11) unsigned NOT NULL AUTO_INCREMENT",
                "CompanyID" => "`CompanyID` int(11) unsigned NOT NULL DEFAULT '0'",
                "UserType" => "`UserType` enum('Staff','Administrator') NOT NULL DEFAULT 'Staff'",
                "Username" => "`Username` varchar(40) NOT NULL DEFAULT ''",
                "Forename" => "`Forename` varchar(40) NOT NULL DEFAULT ''",
                "Surname" => "`Surname` varchar(40) NOT NULL DEFAULT ''",
                "Password" => "`Password` varchar(120) NOT NULL DEFAULT ''",
                "Active" => "`Active` tinyint(3) NOT NULL DEFAULT '0'",
                "Wage" => "`Wage` decimal(8,2) NOT NULL DEFAULT '0.00'"
            ],
            $comparisonSchema->columns
        );

        $this->assertEquals(
            [
                "PRIMARY KEY (`UserID`)",
                "KEY `CompanyID` (`CompanyID`)"
            ], $comparisonSchema->indexes
        );
    }

    public function testSchemaDetectsWhenItCanUpdate()
    {
        $comparisonSchema = MySqlComparisonSchema::fromTable("tblCompany");

        $example = new Company();
        $schema = $example->getSchema();

        $compareTo = MySqlComparisonSchema::fromMySqlSchema($schema);

        $this->assertFalse($compareTo->createAlterTableStatementFor($comparisonSchema));

        $schema->addColumn(new StringColumn("Town", 60, null));

        $compareTo = MySqlComparisonSchema::fromMySqlSchema($schema);

        $this->assertContains("ADD COLUMN `Town` varchar(60) DEFAULT NULL", $compareTo->createAlterTableStatementFor($comparisonSchema));
    }
}