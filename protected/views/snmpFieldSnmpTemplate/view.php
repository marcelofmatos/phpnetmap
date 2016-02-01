<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Field Snmp Templates'=>array('index'),
	$model->id,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu=array(
	array('label'=>'List SnmpFieldSnmpTemplate', 'url'=>array('index')),
	array('label'=>'Create SnmpFieldSnmpTemplate', 'url'=>array('create')),
	array('label'=>'Update SnmpFieldSnmpTemplate', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete SnmpFieldSnmpTemplate', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage SnmpFieldSnmpTemplate', 'url'=>array('admin')),
);
?>

<h1>View SnmpFieldSnmpTemplate #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'snmp_field_id',
		'snmp_template_id',
	),
)); ?>
