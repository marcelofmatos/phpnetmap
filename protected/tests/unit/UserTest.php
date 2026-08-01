<?php

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM user WHERE username LIKE 'test-%' OR username = 'admin'")->execute();
    }

    public function testSetPasswordHashesAndValidatePasswordRoundTrips()
    {
        $user = new User;
        $user->setPassword('correct-horse-battery-staple');

        $this->assertNotSame('correct-horse-battery-staple', $user->password);
        $this->assertTrue($user->validatePassword('correct-horse-battery-staple'));
        $this->assertFalse($user->validatePassword('wrong-password'));
    }

    public function testPasswordIsNotMassAssignable()
    {
        $user = new User;
        $user->attributes = array(
            'username' => 'test-mass-assign',
            'email' => 'test-mass-assign@example.com',
            'password' => 'raw-plaintext',
        );

        $this->assertNull($user->password);
    }

    public function testUsernameMustBeUnique()
    {
        $first = new User;
        $first->username = 'test-unique-user';
        $first->email = 'test-unique@example.com';
        $first->setPassword('whatever');
        $this->assertTrue($first->save());

        $second = new User;
        $second->username = 'test-unique-user';
        $second->email = 'test-unique-2@example.com';
        $second->setPassword('whatever');

        $this->assertFalse($second->validate());
        $this->assertArrayHasKey('username', $second->getErrors());
    }

    public function testSavingWithoutUsernameOrEmailFails()
    {
        $user = new User;
        $user->setPassword('whatever');

        $this->assertFalse($user->save());
    }

    public function testCannotDeleteTheAdminAccount()
    {
        $admin = new User;
        $admin->username = 'admin';
        $admin->email = 'admin@example.com';
        $admin->setPassword('whatever');
        $this->assertTrue($admin->save());

        $this->assertFalse($admin->delete());
        $this->assertNotNull(User::model()->findByAttributes(array('username' => 'admin')));
    }
}
