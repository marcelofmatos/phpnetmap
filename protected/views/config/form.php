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
        'mcpEnabled' => array(
            'type' => 'checkbox'
        ),
        'mcpMode' => array(
            'type' => 'dropdownlist',
            'items' => array('readonly' => 'Read-only', 'readwrite' => 'Read-write'),
        ),
        'authMode' => array(
            'type' => 'dropdownlist',
            'items' => array('yii' => 'PHPNetMap login (recommended)', 'htpasswd' => 'HTTP Basic Auth (.htpasswd, legacy)'),
        ),
    ),
    'buttons' => array(
        'submit' => array('type' => 'submit',
            'value' => '💾 ' . Yii::t('default', 'Save'),
            'class' => 'btn btn-primary')
    )
);
