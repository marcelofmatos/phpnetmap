<?php
/* @var $this VlanController */
/* @var $model Vlan */

$this->breadcrumbs=array(
	'Vlans'=>array('admin'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Vlan', 'url'=>array('index')),
	array('label'=>'Create Vlan', 'url'=>array('create')),
	array('label'=>'View Vlan', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Vlan', 'url'=>array('admin')),
);
?>

<h1>Update Vlan <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>