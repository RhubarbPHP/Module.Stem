<?php
/**
 * Created by JetBrains PhpStorm.
 * User: scott
 * Date: 22/08/2013
 * Time: 10:01
 * To change this template use File | Settings | File Templates.
 */

namespace Rhubarb\Stem;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Decorators\DataDecorator;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Tests\unit\Fixtures\Category;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Fixtures\CompanyDecorator;
use Rhubarb\Stem\Tests\unit\Fixtures\Example;
use Rhubarb\Stem\Tests\unit\Fixtures\ExampleDecorator;
use Rhubarb\Stem\Tests\unit\Fixtures\ModelDecorator;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class DataDecoratorTest extends RhubarbTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        DataDecorator::clearDecoratorClasses();
        DataDecorator::registerDecoratorClass(ExampleDecorator::class, Example::class);
        DataDecorator::registerDecoratorClass(CompanyDecorator::class, Company::class);
    }

    public function testCorrectDecoratorCreated()
    {
        $company = new Company();
        $decorator = DataDecorator::getDecoratorForModel($company);

        $this->assertInstanceOf(CompanyDecorator::class, $decorator);

        $decorator = DataDecorator::getDecoratorForModel(new Category());

        $this->assertFalse($decorator, "If no decorator exists false should be returned.");

        $example = new Example();
        $decorator = DataDecorator::getDecoratorForModel($example);
        $this->assertInstanceOf(ExampleDecorator::class, $decorator);

        DataDecorator::registerDecoratorClass(ModelDecorator::class, Model::class);

        $user = new User();
        $decorator = DataDecorator::getDecoratorForModel($user);

        $this->assertInstanceOf(ModelDecorator::class, $decorator);
    }

    public function testColumnDecorator()
    {
        $company = new Company();
        $company->CompanyName = "Oatfest";

        $decorator = DataDecorator::getDecoratorForModel($company);
        $this->assertEquals("ABCOatfest", $decorator->CompanyName);

        $company->CompanyName = "RyansBoats";
        $this->assertEquals("ABCRyansBoats", $decorator->CompanyName);

        $company = new Company();
        $company->Balance = 34.30;

        $decorator = DataDecorator::getDecoratorForModel($company);
        $this->assertEquals("&pound;34.30", $decorator->Balance);
    }

    public function testColumnFormatter()
    {
        Company::clearObjectCache();

        $company = new Company();
        $company->CompanyName = "abc";
        $company->save();

        $decorator = DataDecorator::getDecoratorForModel($company);

        $this->assertSame("00001", $decorator->CompanyID);
    }

    public function testTypeFormatter()
    {
        $company = new Company();
        $company->Balance = 44.2;

        $decorator = DataDecorator::getDecoratorForModel($company);
        $this->assertEquals("&pound;44.20", $decorator->Balance);
    }

    public function testTypeDecorator()
    {
        $company = new Company();
        $company->InceptionDate = "today";

        $decorator = DataDecorator::getDecoratorForModel($company);

        $this->assertEquals(date("jS F Y"), $decorator->InceptionDate);
    }

    public function testDecoratorIsSingleton()
    {
        $company = new Company();

        $decorator = DataDecorator::getDecoratorForModel($company);
        $decorator->singletonMonitor = true;

        $decorator = DataDecorator::getDecoratorForModel($company);

        $this->assertTrue($decorator->singletonMonitor);

        $example = new Example();
        $decorator = DataDecorator::getDecoratorForModel($example);

        $this->assertFalse($decorator->singletonMonitor);
    }
}