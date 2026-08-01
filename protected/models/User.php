<?php

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $email
 */
class User extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'user';
    }

    /**
     * 'password' is deliberately never listed here — it must never be
     * mass-assignable via $model->attributes = $_POST['User']. It is only
     * ever set through setPassword(), called explicitly by the controller.
     */
    public function rules()
    {
        return array(
            array('username, email', 'required'),
            array('username', 'length', 'max' => 128),
            array('username', 'unique'),
            array('email', 'length', 'max' => 128),
            array('email', 'email'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'email' => 'Email',
        );
    }

    /**
     * Hashes and sets the given plain-text password. The only supported
     * way to change a password.
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @return boolean whether $password matches this user's stored hash
     */
    public function validatePassword($password)
    {
        return $this->password !== null && password_verify($password, $this->password);
    }

    /**
     * @return boolean whether $username is the protected bootstrap admin
     * account name, compared case-insensitively to match how Yii's own
     * CAccessRule::isUserMatched() compares usernames.
     */
    private static function isProtectedAdminUsername($username)
    {
        return strcasecmp($username, 'admin') === 0;
    }

    /**
     * Never allow deleting the bootstrap "admin" account (case-insensitive,
     * matching how Yii's own CAccessRule::isUserMatched() compares
     * usernames) — every admin-gated page in the app (including
     * UserController itself) checks this literal username in its
     * accessRules(); deleting it would lock everyone out permanently.
     */
    protected function beforeDelete()
    {
        if (self::isProtectedAdminUsername($this->username)) {
            return false;
        }
        return parent::beforeDelete();
    }

    /**
     * Never allow renaming the bootstrap "admin" account away from that
     * name — same lockout effect as deleting it, since every admin-gated
     * page checks this literal username. Only blocks the rename itself;
     * saving the admin account with its username unchanged (e.g. to update
     * its email or password) is unaffected, as is renaming any other
     * account.
     */
    protected function beforeSave()
    {
        if (!$this->isNewRecord) {
            $original = self::model()->findByPk($this->id);
            if ($original !== null
                && self::isProtectedAdminUsername($original->username)
                && !self::isProtectedAdminUsername($this->username)
            ) {
                return false;
            }
        }
        return parent::beforeSave();
    }
}
