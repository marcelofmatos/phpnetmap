<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Templates'=>array('index'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu=array(
	array('label'=>'List SnmpTemplate', 'url'=>array('index')),
	array('label'=>'Create SnmpTemplate', 'url'=>array('create')),
	array('label'=>'Update SnmpTemplate', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete SnmpTemplate', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage SnmpTemplate', 'url'=>array('admin')),
        array('label'=>'Manage Snmp Fields', 'url' => array('snmpField/admin')),
);
?>

<h1>View SnmpTemplate #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'version',
		'community',
		'security_name',
		'security_level',
		'auth_protocol',
		'auth_passphrase',
		'priv_protocol',
		'priv_passphrase',
		'timeout',
		'retries',
	),
)); ?>
