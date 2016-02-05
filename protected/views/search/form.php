<?php return array(
        'elements' => array(
                'type' => array(
                        'type' => 'dropdownlist',
                        'items' => SearchForm::getSearchTypes()
                ),
                'hosts' => array(
                    'type' => 'dropdownlist',
                    'items' => CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'),
                    'multiple' => 'multiple',
                    'size' => '12',
                ),
                'query' => array(
                        'type' =>'text'
                ),
                'exact_match' => array(
                        'type' =>'checkbox'
                ),
                'exclude_link_ports' => array(
                        'type' =>'checkbox'
                ),
        ),
        'buttons' => array(
                'submit' => array('type' => 'submit',
                                  'value' => Yii::t('default', 'Search'))
        )
) ;