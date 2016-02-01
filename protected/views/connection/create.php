<?php
/* @var $this ConnectionController */
/* @var $model Connection */

$this->breadcrumbs=array(
	'Connections'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Connection', 'url'=>array('index')),
	array('label'=>'Manage Connection', 'url'=>array('admin')),
);
?>

<h1>Create Connection</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>