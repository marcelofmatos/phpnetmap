<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Templates'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List SnmpTemplate', 'url'=>array('index')),
	array('label'=>'Manage SnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Create SnmpTemplate</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>