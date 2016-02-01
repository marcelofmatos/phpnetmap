<?php
/* @var $this VlanController */
/* @var $model Vlan */

$this->breadcrumbs=array(
	'Vlans'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Vlan', 'url'=>array('index')),
	array('label'=>'Manage Vlan', 'url'=>array('admin')),
);
?>

<h1>Create Vlan</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>