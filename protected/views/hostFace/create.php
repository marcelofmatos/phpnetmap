<?php
/* @var $this HostFaceController */
/* @var $model HostFace */

$this->breadcrumbs=array(
	'Host Faces'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List HostFace', 'url'=>array('index')),
	array('label'=>'Manage HostFace', 'url'=>array('admin')),
);
?>

<h1>Create HostFace</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>