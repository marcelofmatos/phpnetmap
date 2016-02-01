<?php
/* @var $this ConnectionController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Connections',
);

$this->menu=array(
	array('label'=>'Create Connection', 'url'=>array('create')),
	array('label'=>'Manage Connection', 'url'=>array('admin')),
);
?>

<h1>Connections</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
