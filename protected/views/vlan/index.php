<?php
/* @var $this VlanController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Vlans',
);

$this->menu=array(
	array('label'=>'Create Vlan', 'url'=>array('create')),
	array('label'=>'Manage Vlan', 'url'=>array('admin')),
);
?>

<h1>Vlans</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
