<?php

/**
 * This is the model class for table "snmp_field".
 *
 * The followings are the available columns in table 'snmp_field':
 * @property integer $id
 * @property string $snmp_oid
 * @property string $key
 * @property string $label
 */
class SnmpField extends CActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return SnmpField the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'snmp_field';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('snmp_oid, key, label, templates', 'required'),
            array('snmp_oid', 'length', 'max' => 255),
            array('key', 'length', 'max' => 50),
            array('label', 'length', 'max' => 100),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, snmp_oid, key, label', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'templates' => array(self::MANY_MANY, 'SnmpTemplate', 'snmp_field_snmp_template(snmp_field_id,snmp_template_id)'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'snmp_oid' => 'SNMP OID',
            'key' => 'Key',
            'label' => 'Label',
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
        $criteria->compare('snmp_oid', $this->snmp_oid, true);
        $criteria->compare('key', $this->key, true);
        $criteria->compare('label', $this->label, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * save relations
     * ref.: http://www.yiiframework.com/extension/cadvancedarbehavior/
     * @return type
     */
    public function behaviors() {
        return array('CAdvancedArBehavior' => array(
                'class' => 'application.extensions.CAdvancedArBehavior')
        );
    }

}