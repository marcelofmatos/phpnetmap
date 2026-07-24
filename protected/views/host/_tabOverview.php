<?php
/* @var $this HostController */
/* @var $model Host */
?>
<?php $this->widget('bootstrap.widgets.TbDetailView', array(
    'data' => $model,
    'attributes' => array(
        'id',
        'name',
        'type',
        'mac',
        'ip',
        'snmpTemplate',
        array(
            'name' => 'InfoSerialNumber',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoSerialNumber); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoModel',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoModel); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoSystem',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoSystem); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoUptime',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoUptime); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoContact',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoContact); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoLocation',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoLocation); },
            'type' => 'raw',
        ),
    ),
)); ?>
