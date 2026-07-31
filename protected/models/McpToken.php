<?php

class McpToken extends CActiveRecord
{
    public $description;
    public $token_hash;
    public $token_prefix;
    public $expires_at;
    public $last_used_at;
    public $created_at;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'mcp_token';
    }

    public function rules()
    {
        return array(
            array('description, expires_at', 'required'),
            array('description', 'length', 'max' => 255),
            array('expires_at', 'date', 'format' => 'yyyy-MM-dd'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'description' => 'Description',
            'expires_at' => 'Expires At',
            'last_used_at' => 'Last Used At',
            'created_at' => 'Created At',
        );
    }

    /**
     * Generates a new raw token, stores only its hash/prefix on this model,
     * and returns the raw value — the only time it's ever available.
     * @return string
     */
    public function generateToken()
    {
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->token_hash = hash('sha256', $raw);
        $this->token_prefix = substr($raw, 0, 8);
        $this->created_at = date('Y-m-d H:i:s');
        return $raw;
    }

    /**
     * @param string $rawToken
     * @return McpToken|null
     */
    public static function findValidByRawToken($rawToken)
    {
        $token = self::model()->findByAttributes(array('token_hash' => hash('sha256', $rawToken)));
        if ($token === null || $token->isExpired()) {
            return null;
        }
        return $token;
    }

    public function isExpired()
    {
        return strtotime($this->expires_at . ' 23:59:59') < time();
    }

    public function touchLastUsed()
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        $this->update(array('last_used_at'));
    }
}
