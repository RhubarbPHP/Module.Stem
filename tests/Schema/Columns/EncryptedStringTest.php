<?php

namespace Rhubarb\Stem\Tests\Schema\Columns;

use Rhubarb\Crown\Encryption\EncryptionProvider;
use Rhubarb\Crown\Encryption\UnitTesting\UnitTestingAes256EncryptionProvider;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\ModelSchema;
use Rhubarb\Crown\Tests\RhubarbTestCase;

class EncryptedStringTest extends RhubarbTestCase
{
	protected function setUp()
	{
		parent::setUp();

		EncryptionProvider::SetEncryptionProviderClassName( '\Rhubarb\Crown\Encryption\UnitTesting\UnitTestingAes256EncryptionProvider' );
	}

	public function testEncryption()
	{
		$model = new TestModel();
		$model->SecureColumn = "plain text";

		$aes = new UnitTestingAes256EncryptionProvider();

		$this->assertEquals( $aes->Encrypt( "plain text", "SecureColumn" ), $model->ExportRawData()[ "SecureColumn" ] );
	}

	public function testDecryption()
	{
		$model = new TestModel();
		$model->SecureColumn = "plain text";

		// Assuming the encryption test passed, then this simple code will test the decryption.

		$this->assertEquals( "plain text", $model->SecureColumn );
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
		$schema = new ModelSchema( "Test" );
		$schema->addColumn( new EncryptedString( "SecureColumn", 100 ) );

		return $schema;
	}
}
 