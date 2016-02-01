<?php

return array(
    'elements' => array(
        'adminEmail' => array(
            'type' => 'text',
        ),
        'translateCamTable' => array(
            'type' => 'checkbox'
        ),
        'hostGatewayId' => array(
            'type' => 'dropdownlist',
            'items' => CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'),
        ),
        'cache' => array(
            'type' => 'checkbox'
        ),
        'cacheTtlCam' => array(
            'type' => 'text'
        ),
        'cacheTtlArp' => array(
            'type' => 'text'
        ),
        'cacheTtlGetSnmp' => array(
            'type' => 'text'
        ),
        'cacheTtlDefault' => array(
            'type' => 'text'
        ),
    ),
    'buttons' => array(
        'submit' => array('type' => 'submit',
            'value' => Yii::t('default', 'Save'))
    )
);
