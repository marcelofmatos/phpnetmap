<?php
/* @var $this HostFaceController */
/* @var $model HostFace */

$this->breadcrumbs=array(
	'Host Faces'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List HostFace', 'url'=>array('index')),
	array('label'=>'Create HostFace', 'url'=>array('create')),
	array('label'=>'View HostFace', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage HostFace', 'url'=>array('admin')),
);
?>

<h1>Update HostFace <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>