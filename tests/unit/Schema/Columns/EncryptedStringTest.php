<?php

namespace Rhubarb\Stem\Tests\unit\Schema\Columns;

use Rhubarb\Crown\Encryption\EncryptionProvider;
use Rhubarb\Crown\Tests\Encryption\UnitTestingAes256EncryptionProvider;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\EncryptedString;
use Rhubarb\Stem\Schema\ModelSchema;

class EncryptedStringTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        EncryptionProvider::SetEncryptionProviderClassName(UnitTestingAes256EncryptionProvider::class);
    }

    public function testEncryption()
    {
        $model = new TestModel();
        $model->SecureColumn = "plain text";

        $aes = new UnitTestingAes256EncryptionProvider();

        $this->assertEquals($aes->Encrypt("plain text", "SecureColumn"), $model->ExportRawData()["SecureColumn"]);
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
        $schema->addColumn(new EncryptedString("SecureColumn", 100));

        return $schema;
    }
}
