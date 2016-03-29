<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Encryption\EncryptionProvider;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Crown\Tests\unit\Encryption\UnitTestingAes256EncryptionProvider;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\EncryptedStringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class EncryptedStringTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        EncryptionProvider::setEncryptionProviderClassName(UnitTestingAes256EncryptionProvider::class);
    }

    public function testEncryption()
    {
        $model = new TestModel();
        $model->SecureColumn = "plain text";

        $aes = new UnitTestingAes256EncryptionProvider();

        $this->assertEquals($aes->encrypt("plain text", "SecureColumn"), $model->exportRawData()["SecureColumn"]);
    }

    public function testDecryption()
    {
        $model = new TestModel();
        $model->SecureColumn = "plain text";

        // Assuming the encryption test passed, then this simple code will test the decryption.

        $this->assertEquals("plain text", $model->SecureColumn);
    }
}

class TestModel extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("Test");
        $schema->addColumn(new EncryptedStringColumn("SecureColumn", 100));

        return $schema;
    }
}
