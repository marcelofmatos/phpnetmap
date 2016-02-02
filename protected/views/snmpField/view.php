<?php
/* @var $this SnmpFieldController */
/* @var $model SnmpField */

$this->breadcrumbs=array(
	'Snmp Fields'=>array('admin'),
	$model->id,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu=array(
	array('label'=>'List SnmpField', 'url'=>array('index')),
	array('label'=>'Create SnmpField', 'url'=>array('create')),
	array('label'=>'Update SnmpField', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete SnmpField', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage SnmpField', 'url'=>array('admin')),
);
?>

<h1>View SnmpField #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'snmp_oid',
		'key',
		'label',
	),
)); ?>
