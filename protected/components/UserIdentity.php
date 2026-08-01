<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;

    /**
     * Authenticates a user based on {@link username} and {@link password}.
     * @return boolean whether authentication succeeds.
     */
    public function authenticate()
    {
        $user = User::model()->findByAttributes(array('username' => $this->username));

        if ($user === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } elseif (!$user->validatePassword($this->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } else {
            $this->_id = $user->id;
            $this->setState('email', $user->email);
            $this->errorCode = self::ERROR_NONE;
        }

        return $this->errorCode === self::ERROR_NONE;
    }

    // getName() is intentionally NOT overridden — it stays at CUserIdentity's
    // inherited default, which returns $this->username. Every controller's
    // accessRules() throughout this app checks the literal string 'admin'
    // against Yii::app()->user->name (i.e. getName()), so it must keep
    // returning the raw username, not the numeric id.

    /**
     * Returns the numeric primary key of the authenticated User row,
     * overriding CUserIdentity's default (which returns the username).
     */
    public function getId()
    {
        return $this->_id;
    }
}
