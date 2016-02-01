<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Templates'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List SnmpTemplate', 'url'=>array('index')),
	array('label'=>'Create SnmpTemplate', 'url'=>array('create')),
	array('label'=>'View SnmpTemplate', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage SnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Update SnmpTemplate <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>