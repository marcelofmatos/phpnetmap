<?php
/* @var $this HostFaceController */
/* @var $model HostFace */

$this->breadcrumbs=array(
	'Host Faces'=>array('index'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu=array(
	array('label'=>'List HostFace', 'url'=>array('index')),
	array('label'=>'Create HostFace', 'url'=>array('create')),
	array('label'=>'Update HostFace', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete HostFace', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage HostFace', 'url'=>array('admin')),
);
?>

<h1>View HostFace #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		array(
                    'type' => 'raw',
                    'name' => 'svg',
                ),
	),
)); ?>
