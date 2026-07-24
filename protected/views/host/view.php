<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

?>
<?php $this->renderPartial('/host/_header', array('model' => $model)); ?>
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
