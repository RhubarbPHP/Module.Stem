<?php

namespace Gcd\Tests;
use Rhubarb\Stem\Tests\Fixtures\TestLoginProvider;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class ModelLoginProviderTest extends \Rhubarb\Crown\Tests\RhubarbTestCase
{
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		\Rhubarb\Crown\Encryption\HashProvider::SetHashProviderClassName( "Rhubarb\Crown\Encryption\Sha512HashProvider" );

		$user = new \Rhubarb\Stem\Tests\Fixtures\User();
		$user->Username = "billy";
		$user->Password = '$6$rounds=10000$EQeQYSJmy6UAzGDb$7MoO7FLWXex8GDHkiY/JNk5ukXpUHDKfzs3S5Q04IdB8Xz.W2qp1zZ7/oVWrFZrCX7qKckJNeBDwRC.rmVR/Q1';
		$user->Active = false;
		$user->save();

		$user = new \Rhubarb\Stem\Tests\Fixtures\User();
		$user->Username = "mdoe";
		$user->Password = '$6$rounds=10000$EQeQYSJmy6UAzGDb$7MoO7FLWXex8GDHkiY/JNk5ukXpUHDKfzs3S5Q04IdB8Xz.W2qp1zZ7/oVWrFZrCX7qKckJNeBDwRC.rmVR/Q1';
		$user->Active = true;
		// This secret property is used to test the model object is returned correctly.
		$user->SecretProperty = "111222";
		$user->save();

		// This rogue entry is to make sure that we can't login with no username
		// even if there happens to be someone with no username.
		$user = new \Rhubarb\Stem\Tests\Fixtures\User();
		$user->Username = "";
		$user->Password = "";
		$user->save();
	}

	public function testLoginChecksUsernameIsNotBlank()
	{
		$this->setExpectedException( "Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException" );

		$testLoginProvider = new TestLoginProvider();
		$testLoginProvider->login( "", "" );
	}

	public function testLoginChecksUsername()
	{
		$this->setExpectedException( "Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException" );

		$testLoginProvider = new TestLoginProvider();
		$testLoginProvider->login( "noname", "nopassword" );
	}

	public function testLoginChecksDisabled()
	{
		$this->setExpectedException( "Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException" );

		$testLoginProvider = new TestLoginProvider();
		$testLoginProvider->login( "billy", "abc123" );
	}

	public function testLoginChecksPasswordAndThrows()
	{
		$this->setExpectedException( "Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException" );

		$testLoginProvider = new TestLoginProvider();
		$testLoginProvider->login( "mdoe", "badpassword" );
	}

	public function testLoginChecksPasswordReturnsModelAndLogsOut()
	{
		$testLoginProvider = new TestLoginProvider();

		try
		{
			$testLoginProvider->login( "mdoe", "badpassword" );
		}
		catch( \Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException $er )
		{
		}

		$this->assertFalse( $testLoginProvider->IsLoggedIn() );

		$result = $testLoginProvider->login( "mdoe", "abc123" );

		$this->assertTrue( $result );
		$this->assertTrue( $testLoginProvider->IsLoggedIn() );

		$model = $testLoginProvider->getModel();

		$this->assertInstanceOf( "Rhubarb\Stem\Tests\Fixtures\User", $model );
		$this->assertEquals( "111222", $model->SecretProperty );

		$this->assertNotNull( $testLoginProvider->LoggedInUserIdentifier );

		$testLoginProvider->LogOut();

		$this->assertFalse( $testLoginProvider->IsLoggedIn() );
		$this->assertNull( $testLoginProvider->LoggedInUserIdentifier );

		$this->setExpectedException( "Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException" );

		$model = $testLoginProvider->getModel();
	}

    public function testForceLogin()
    {
        $user = new \Rhubarb\Stem\Tests\Fixtures\User();
        $user->Username = "flogin";
        $user->save();

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->forceLogin( $user );

        $this->assertTrue( $testLoginProvider->IsLoggedIn() );
        $this->assertEquals( $user->UniqueIdentifier, $testLoginProvider->getModel()->UniqueIdentifier );
    }
}
