<?php

namespace Rhubarb\Stem\Tests\unit\LoginProviders;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
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
        $user->Username = "billy";        $user->Password = '$6$rounds=10000$EQeQYSJmy6UAzGDb$7MoO7FLWXex8GDHkiY/JNk5ukXpUHDKfzs3S5Q04IdB8Xz.W2qp1zZ7/oVWrFZrCX7qKckJNeBDwRC.rmVR/Q1';
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

    public function testForceLoginInLogOutAndGetModel()
    {
        $testLoginProvider = new TestLoginProvider();

        $user = new User();
        $user->Username = "billy";
        $user->Active = false;
        $user->save();

        $testLoginProvider->forceLogin($user);
        $this->assertTrue($testLoginProvider->isLoggedIn());
        $this->assertEquals($user->UniqueIdentifier, $testLoginProvider->getModel()->UniqueIdentifier);

        $testLoginProvider->logOut();

        $this->assertFalse($testLoginProvider->isLoggedIn());

        $this->setExpectedException(NotLoggedInException::class);

        $model = $testLoginProvider->getModel();
    }
}
