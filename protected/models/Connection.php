<?php

/**
 * This is the model class for table "connection".
 *
 * The followings are the available columns in table 'connection':
 * @property integer $id
 * @property integer $host_src_id
 * @property integer $host_src_port
 * @property integer $host_dst_id
 * @property integer $host_dst_port
 * @property string $type
 *
 * The followings are the available model relations:
 * @property Host $hostDst
 * @property Host $hostSrc
 */
class Connection extends CActiveRecord
{
    
        const TYPE_UNKNOWN = 'unknown';
        const TYPE_SUPPOSED_LINK = "supposed_link";
        const TYPE_UTP_CAT5 = 'utp_cat5';
        const TYPE_UTP_CAT5E = 'utp_cat5e';
        const TYPE_UTP_CAT6 = 'utp_cat6';
        const TYPE_FIBER = 'fiber';
        const TYPE_WIRELESS = 'wireless';
        
        // VLAN info from CAM table Host
        var $vlan;

        /**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Connection the static model class
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
		return 'connection';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('host_src_id, host_src_port, host_dst_id, host_dst_port', 'required'),
			array('host_src_id, host_src_port, host_dst_id, host_dst_port', 'numerical', 'integerOnly'=>true),
			array('type', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, host_src_id, host_src_port, host_dst_id, host_dst_port, type, hostSrc, hostDst', 'safe', 'on'=>'search'),
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
			'hostDst' => array(self::BELONGS_TO, 'Host', 'host_dst_id'),
			'hostSrc' => array(self::BELONGS_TO, 'Host', 'host_src_id'),
			'vlan' => array(self::BELONGS_TO, 'Vlan', 'tag'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'host_src_id' => 'Host Src',
			'host_src_port' => 'Host Src Port',
			'host_dst_id' => 'Host Dst',
			'host_dst_port' => 'Host Dst Port',
			'type' => 'Type',
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
		$criteria->compare('host_src_id',$this->host_src_id);
		$criteria->compare('host_src_port',$this->host_src_port);
		$criteria->compare('host_dst_id',$this->host_dst_id);
		$criteria->compare('host_dst_port',$this->host_dst_port);
		$criteria->compare('type',$this->type,true);
                
                $criteria->with = array('hostSrc','hostDst');
                $criteria->addSearchCondition('hostSrc.name',$this->hostSrc);
                $criteria->addSearchCondition('hostDst.name',$this->hostDst);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
        
        /**
         * type labels
         * @return array
         */
        public function getTypes() {
            return array(
                self::TYPE_UNKNOWN => 'Unknown',
                self::TYPE_UTP_CAT5 => 'UTP Cat 5',
                self::TYPE_UTP_CAT5E => 'UTP Cat 5e',
                self::TYPE_UTP_CAT6 => 'UTP Cat 6',
                self::TYPE_FIBER => 'Fiber',
                self::TYPE_WIRELESS => 'Wireless',
            );
        }
        public function defaultScope() {
            return array('order' => 'host_src_id, host_src_port');
        }
}