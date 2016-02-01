<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu = array(
    array('label' => 'Web Config. ' . $model->name , 'url' => 'http://' . $model->ip, 'linkOptions' => array('target'=>'_blank')),
    array('label' => 'Show Host Connections', 'url' => array('host/connections','name' => $model->name )),
    array('label' => 'Show ARP Table', 'url' => array('host/arpTable', 'name' => $model->name )),
    array('label' => 'Show CAM Table', 'url' => array('host/camTable', 'name' => $model->name )),
    array('label' => 'Show Traffic', 'url' => array('host/traffic', 'name' => $model->name )),
    array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
    array('label' => 'Delete Host', 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm' => 'Are you sure you want to delete this item?')),
    array('label' => 'Manage Host', 'url' => array('admin')),
    array('label' => 'Manage Host Faces', 'url' => array('hostFace/index')),
    array('label' => 'List Host', 'url' => array('index')),
    array('label' => 'Create Host', 'url' => array('create')),
);
?>
<table>
    <tr>
        <td>
            <?php

            $this->renderPartial('/map/_view', array(
                'height' => 300,
                'width' => 800,
                'navigation' => true,
                'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
            ));
            ?>
        </td>
    </tr>
    
    <tr>
        <td>
            <?php $this->renderPartial('/host/_ports', array('model' => $model)); ?>
        </td>
    </tr>

    <tr>
        <td width="70%" style="vertical-align: top">
            <h3>Info:</h3>
            <?php
            $this->widget('bootstrap.widgets.TbDetailView', array(
                'data' => $model,
                'attributes' => array(
                        'id',
                        'name',
                        'type',
                        'mac',
                        'ip',
                        'snmpTemplate',
                        'InfoSerialNumber',
                        'InfoModel',
                        'InfoSystem',
                        'InfoUptime',
                        'InfoContact',
                        'InfoLocation', 
                ),
            ));
            ?>
        </td>
    </tr>
</table>
