<?php
/* @var $this ConnectionController */
/* @var $model Connection */

$this->breadcrumbs=array(
	'Connections'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Connection', 'url'=>array('index')),
	array('label'=>'Create Connection', 'url'=>array('create')),
	array('label'=>'Update Connection', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Update Host '.$model['hostSrc']->name, 'url'=>array('host/update', 'id'=>$model['hostSrc']->id)),
	array('label'=>'Update Host '.$model['hostDst']->name, 'url'=>array('host/update', 'id'=>$model['hostDst']->id)),
	array('label'=>'Delete Connection', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Connection', 'url'=>array('admin')),
);
?>

<h1>View Connection #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
                array(
                    'name' => 'hostSrc',
                    'value' => CHtml::link($model['hostSrc']->name, Yii::app()->createUrl("host/viewByName",array("name"=>$model['hostSrc']->name))),
                    'type' => 'raw',
                ),
		'host_src_port',
                array(
                    'name' => 'hostDst',
                    'value' => CHtml::link($model['hostDst']->name, Yii::app()->createUrl("host/viewByName",array("name"=>$model['hostDst']->name))),
                    'type' => 'raw',
                ),
		'host_dst_port',
		'type',
	),
)); ?>
