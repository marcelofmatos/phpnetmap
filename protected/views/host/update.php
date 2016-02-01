<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name=>array('viewByName','name'=>$model->name),
	'Update',
);

$this->menu=array(
	array('label'=>'List Host', 'url'=>array('index')),
	array('label'=>'Create Host', 'url'=>array('create')),
	array('label'=>'View Host', 'url'=>array('viewByName', 'name'=>$model->name)),
	array('label'=>'Manage Host', 'url'=>array('admin')),
);
?>
<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>