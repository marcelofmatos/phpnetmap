<?php

use PHPUnit\Framework\TestCase;

class UserIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        $user = new User;
        $user->username = 'identitytest';
        $user->email = 'identitytest@example.com';
        $user->setPassword('s3cret');
        $user->save(false);
    }

    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM user WHERE username = 'identitytest'")->execute();
    }

    public function testAuthenticateSucceedsWithCorrectCredentials()
    {
        $identity = new UserIdentity('identitytest', 's3cret');
        $this->assertTrue($identity->authenticate());
        $this->assertSame(UserIdentity::ERROR_NONE, $identity->errorCode);
        $this->assertSame('identitytest', $identity->getName());

        $expectedUser = User::model()->findByAttributes(array('username' => 'identitytest'));
        $this->assertSame($expectedUser->id, $identity->getId());
    }

    public function testAuthenticateFailsWithWrongPassword()
    {
        $identity = new UserIdentity('identitytest', 'wrong-password');
        $this->assertFalse($identity->authenticate());
        $this->assertSame(UserIdentity::ERROR_PASSWORD_INVALID, $identity->errorCode);
    }

    public function testAuthenticateFailsWithUnknownUsername()
    {
        $identity = new UserIdentity('does-not-exist', 'whatever');
        $this->assertFalse($identity->authenticate());
        $this->assertSame(UserIdentity::ERROR_USERNAME_INVALID, $identity->errorCode);
    }
}
