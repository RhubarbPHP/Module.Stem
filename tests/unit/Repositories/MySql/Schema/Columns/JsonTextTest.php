<?php
/**
 * Created by PhpStorm.
 * User: cdoherty
 * Date: 01/08/14
 * Time: 14:52
 */

namespace Rhubarb\Stem\Tests\unit\Repositories\MySql\Schema\Columns;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\MySql;
use Rhubarb\Stem\Tests\unit\Fixtures\Company;
use Rhubarb\Stem\Tests\unit\Repositories\MySql\MySqlTestCase;

class JsonTextTest extends MySqlTestCase
{
    private $constraint;
    private $data;

    public function setUp()
    {
        $this->constraint = '{"a":1,"b":2,"c":3}';

        $this->data = new \stdClass();
        $this->data->a = 1;
        $this->data->b = 2;
        $this->data->c = 3;
    }

    public function testValidJsonTransform()
    {
        $company = new Company();
        $company->CompanyName = "Gcd Technologies";
        $company->CompanyData = $this->data;
        $company->save();

        $params = MySql::getPreviousParameters();

        $this->assertEquals($this->constraint, $params["CompanyData"]);

        Model::clearAllRepositories();

        $company = new Company($company->UniqueIdentifier);
        $this->assertEquals($this->data, $company->CompanyData);
    }
}