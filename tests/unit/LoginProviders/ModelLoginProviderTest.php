<?php

namespace Rhubarb\Stem\Tests\unit\LoginProviders;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Stem\Tests\unit\Fixtures\TestExpiredLoginProvider;
use Rhubarb\Stem\Tests\unit\Fixtures\TestExpiredUser;
use Rhubarb\Stem\Tests\unit\Fixtures\TestLoginProvider;
use Rhubarb\Stem\Tests\unit\Fixtures\User;

class ModelLoginProviderTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        User::clearObjectCache();

        HashProvider::setProviderClassName(Sha512HashProvider::class);

        $user = new User();
        $user->Username = "billy";
        $user->Password = '$6$rounds=10000$EQeQYSJmy6UAzGDb$7MoO7FLWXex8GDHkiY/JNk5ukXpUHDKfzs3S5Q04IdB8Xz.W2qp1zZ7/oVWrFZrCX7qKckJNeBDwRC.rmVR/Q1';
        $user->Active = false;
        $user->save();

        $user = new User();
        $user->Username = "mdoe";
        $user->Password = '$6$rounds=10000$EQeQYSJmy6UAzGDb$7MoO7FLWXex8GDHkiY/JNk5ukXpUHDKfzs3S5Q04IdB8Xz.W2qp1zZ7/oVWrFZrCX7qKckJNeBDwRC.rmVR/Q1';
        $user->Active = true;
        // This secret property is used to test the model object is returned correctly.
        $user->SecretProperty = "111222";
        $user->save();

        // This rogue entry is to make sure that we can't login with no username
        // even if there happens to be someone with no username.
        $user = new User();
        $user->Username = "";
        $user->Password = "";
        $user->save();
    }

    public function testLoginChecksUsernameIsNotBlank()
    {
        $this->setExpectedException(LoginFailedException::class);

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->login("", "");
    }

    public function testLoginChecksUsername()
    {
        $this->setExpectedException(LoginFailedException::class);

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->login("noname", "nopassword");
    }

    public function testLoginChecksDisabled()
    {
        $this->setExpectedException(LoginDisabledException::class);

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->login("billy", "abc123");
    }

    public function testLoginChecksPasswordAndThrows()
    {
        $this->setExpectedException(LoginFailedException::class);

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->login("mdoe", "badpassword");
    }

    public function testLoginChecksPasswordReturnsModelAndLogsOut()
    {
        $testLoginProvider = new TestLoginProvider();

        try {
            $testLoginProvider->login("mdoe", "badpassword");
        } catch (LoginFailedException $er) {
        }

        $this->assertFalse($testLoginProvider->isLoggedIn());

        $result = $testLoginProvider->login("mdoe", "abc123");

        $this->assertTrue($result);
        $this->assertTrue($testLoginProvider->isLoggedIn());

        $model = $testLoginProvider->getModel();

        $this->assertInstanceOf(User::class, $model);
        $this->assertEquals("111222", $model->SecretProperty);

        $testLoginProvider->LogOut();

        $this->assertFalse($testLoginProvider->isLoggedIn());

        $this->setExpectedException(NotLoggedInException::class);

        $model = $testLoginProvider->getModel();
    }

    public function testForceLogin()
    {
        $user = new User();
        $user->Username = "flogin";
        $user->save();

        $testLoginProvider = new TestLoginProvider();
        $testLoginProvider->forceLogin($user);

        $this->assertTrue($testLoginProvider->isLoggedIn());
        $this->assertEquals($user->UniqueIdentifier, $testLoginProvider->getModel()->UniqueIdentifier);
    }

    public function testExpiredLogin()
    {
        $user = new TestExpiredUser();
        $user->Username = "expiredlogin";
        $user->Password = "password";
        $user->save();

        try {
            $testLoginProvider = new TestExpiredLoginProvider();
            $testLoginProvider->login($user->Username, $user->Password);

            $this->fail("Expected User login to be expired");
        } catch (LoginExpiredException $exception) {
            $this->assertEquals("Sorry, your login has now expired. Please contact the system administrator to address this issue.", $exception->getPublicMessage());
        }
    }
}
