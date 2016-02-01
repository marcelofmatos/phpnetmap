<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Snmp Field Snmp Templates',
);

$this->menu=array(
	array('label'=>'Create SnmpFieldSnmpTemplate', 'url'=>array('create')),
	array('label'=>'Manage SnmpFieldSnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Snmp Field Snmp Templates</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
