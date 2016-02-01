<?php
/* @var $this SnmpFieldController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Snmp Fields',
);

$this->menu=array(
	array('label'=>'Create SnmpField', 'url'=>array('create')),
	array('label'=>'Manage SnmpField', 'url'=>array('admin')),
);
?>

<h1>Snmp Fields</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
