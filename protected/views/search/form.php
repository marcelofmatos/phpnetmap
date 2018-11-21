<?php return array(
        'elements' => array(
                'type' => array(
                        'type' => 'dropdownlist',
                        'class' => 'span3',
                        'items' => SearchForm::getSearchTypes()
                ),
                'hosts' => array(
                    'type' => 'dropdownlist',
                    'class' => 'span3',
                    'items' => CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'),
                    'multiple' => 'multiple',
                    'size' => '12'
                ),
                'query' => array(
                        'type' =>'text',
                        'class' => 'span3'
                ),
                'exact_match' => array(
                        'type' =>'checkbox'
                ),
                'exclude_link_ports' => array(
                        'type' =>'checkbox'
                ),
        ),
        'buttons' => array(
                'submit' => array(
                'type' => 'submit',
                'value' => Yii::t('default', 'Search'),
                'class' => 'span3 btn btn-primary'
                )
        )
) ;