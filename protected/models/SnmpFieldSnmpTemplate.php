<?php

/**
 * This is the model class for table "snmp_field_snmp_template".
 *
 * The followings are the available columns in table 'snmp_field_snmp_template':
 * @property integer $id
 * @property integer $snmp_field_id
 * @property integer $snmp_template_id
 *
 * The followings are the available model relations:
 * @property SnmpTemplate $snmpTemplate
 * @property SnmpField $snmpField
 */
class SnmpFieldSnmpTemplate extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return SnmpFieldSnmpTemplate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'snmp_field_snmp_template';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('snmp_field_id, snmp_template_id', 'required'),
			array('snmp_field_id, snmp_template_id', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, snmp_field_id, snmp_template_id', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'snmpTemplate' => array(self::BELONGS_TO, 'SnmpTemplate', 'snmp_template_id'),
			'snmpField' => array(self::BELONGS_TO, 'SnmpField', 'snmp_field_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'snmp_field_id' => 'Snmp Field',
			'snmp_template_id' => 'Snmp Template',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('snmp_field_id',$this->snmp_field_id);
		$criteria->compare('snmp_template_id',$this->snmp_template_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}