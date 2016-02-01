<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Field Snmp Templates'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List SnmpFieldSnmpTemplate', 'url'=>array('index')),
	array('label'=>'Manage SnmpFieldSnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Create SnmpFieldSnmpTemplate</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>