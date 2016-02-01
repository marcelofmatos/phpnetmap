<?php

/**
 * This is the model class for table "vlan".
 *
 * The followings are the available columns in table 'vlan':
 * @property integer $id
 * @property integer $tag
 * @property string $name
 * @property string $description
 * @property string $background_color
 * @property string $font_color
 *
 * The followings are the available model relations:
 * @property HostInterface[] $hostInterfaces
 */
class Vlan extends CActiveRecord
{

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Vlan the static model class
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
		return 'vlan';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('tag, name', 'required'),
			array('tag', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>50),
			array('background_color, font_color', 'length', 'max'=>6),
			array('description', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, tag, name, description, background_color, font_color', 'safe', 'on'=>'search'),
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
			'hostInterfaces' => array(self::HAS_MANY, 'HostInterface', 'vlan_tag'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'tag' => 'Tag',
			'name' => 'Name',
			'description' => 'Description',
			'background_color' => 'Background Color',
			'font_color' => 'Font Color',
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
		$criteria->compare('tag',$this->tag);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('background_color',$this->background_color,true);
		$criteria->compare('font_color',$this->font_color,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
        
        public function defaultScope() {
            return array(
                'order' => $this->getTableAlias(false, false) . '.tag'
                );
        }
        
        public function __toString() {
            return 'VLAN ' . $this->tag . ' - ' . $this->name;
        }
}