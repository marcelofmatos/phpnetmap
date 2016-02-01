<?php

/**
 * This is the model class for table "snmp_template".
 *
 * The followings are the available columns in table 'snmp_template':
 * @property integer $id
 * @property string $name
 * @property string $version
 * @property string $community
 * @property string $security_name
 * @property string $security_level
 * @property string $auth_protocol
 * @property string $auth_passphrase
 * @property string $priv_protocol
 * @property string $priv_passphrase
 * @property integer $timeout
 * @property integer $retries
 *
 * The followings are the available model relations:
 * @property SnmpFieldSnmpTemplate[] $snmpFieldSnmpTemplates
 * @property Host[] $hosts
 */
class SnmpTemplate extends CActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return SnmpTemplate the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'snmp_template';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('timeout, retries', 'numerical', 'integerOnly' => true),
            array('name, auth_protocol, priv_protocol', 'length', 'max' => 50),
            array('version', 'length', 'max' => 3),
            array('community, security_name, security_level, auth_passphrase, priv_passphrase', 'length', 'max' => 255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, version, community, security_name, security_level, auth_protocol, auth_passphrase, priv_protocol, priv_passphrase, timeout, retries', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'hosts' => array(self::HAS_MANY, 'Host', 'snmp_template_id'),
            'fields' => array(self::MANY_MANY, 'SnmpField', 'snmp_field_snmp_template(snmp_template_id,snmp_field_id)'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'version' => 'Version',
            'community' => 'Community',
            'security_name' => 'Security Name',
            'security_level' => 'Security Level',
            'auth_protocol' => 'Auth Protocol',
            'auth_passphrase' => 'Auth Passphrase',
            'priv_protocol' => 'Priv Protocol',
            'priv_passphrase' => 'Priv Passphrase',
            'timeout' => 'Timeout',
            'retries' => 'Retries',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('version', $this->version, true);
        $criteria->compare('community', $this->community, true);
        $criteria->compare('security_name', $this->security_name, true);
        $criteria->compare('security_level', $this->security_level, true);
        $criteria->compare('auth_protocol', $this->auth_protocol, true);
        $criteria->compare('auth_passphrase', $this->auth_passphrase, true);
        $criteria->compare('priv_protocol', $this->priv_protocol, true);
        $criteria->compare('priv_passphrase', $this->priv_passphrase, true);
        $criteria->compare('timeout', $this->timeout);
        $criteria->compare('retries', $this->retries);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    public function __toString() {
        return $this->name;
    }

    public function defaultScope() {
        return array(
            'order' => $this->getTableAlias(false, false) . '.name'
        );
    }

    /**
     * save relations
     * ref.: http://www.yiiframework.com/extension/save-relations-ar-behavior/#hh3
     * @return type
     */
    public function behaviors() {
        return array('CAdvancedArBehavior' => array(
                'class' => 'application.extensions.CAdvancedArBehavior')
        );
    }

}