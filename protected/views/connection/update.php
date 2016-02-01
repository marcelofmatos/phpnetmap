<?php
/* @var $this ConnectionController */
/* @var $model Connection */

$this->breadcrumbs=array(
	'Connections'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Connection', 'url'=>array('index')),
	array('label'=>'Create Connection', 'url'=>array('create')),
	array('label'=>'View Connection', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Connection', 'url'=>array('admin')),
);
?>

<h1>Update Connection <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>