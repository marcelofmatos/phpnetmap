<?php
/* @var $this HostFaceController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Host Faces',
);

$this->menu=array(
	array('label'=>'Create HostFace', 'url'=>array('create')),
	array('label'=>'Manage HostFace', 'url'=>array('admin')),
);
?>

<h1>Host Faces</h1>

<?php $this->widget('bootstrap.widgets.TbListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
