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
     * Never allow deleting the bootstrap "admin" account — every admin-gated
     * page in the app (including UserController itself) checks the literal
     * username 'admin' in its accessRules(); deleting it would lock everyone
     * out permanently.
     */
    protected function beforeDelete()
    {
        if ($this->username === 'admin') {
            return false;
        }
        return parent::beforeDelete();
    }
}
