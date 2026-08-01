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

    /**
     * getName() is intentionally left at its CUserIdentity-inherited
     * default (returns $this->username) — every controller's accessRules()
     * checks the literal string 'admin' against this via
     * CAccessRule::isUserMatched(), so it must keep returning the raw
     * username, not the numeric id.
     */
    public function getId()
    {
        return $this->_id;
    }
}
