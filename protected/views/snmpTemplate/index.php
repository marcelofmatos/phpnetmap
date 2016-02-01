<?php
/* @var $this SnmpTemplateController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Snmp Templates',
);

$this->menu=array(
	array('label'=>'Create SnmpTemplate', 'url'=>array('create')),
	array('label'=>'Manage SnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Snmp Templates</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
