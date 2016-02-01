<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Field Snmp Templates'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List SnmpFieldSnmpTemplate', 'url'=>array('index')),
	array('label'=>'Create SnmpFieldSnmpTemplate', 'url'=>array('create')),
	array('label'=>'View SnmpFieldSnmpTemplate', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage SnmpFieldSnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>Update SnmpFieldSnmpTemplate <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>