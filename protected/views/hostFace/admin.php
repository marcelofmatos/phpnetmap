<?php
/* @var $this HostFaceController */
/* @var $model HostFace */

$this->breadcrumbs=array(
	'Host Faces'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List HostFace', 'url'=>array('index')),
	array('label'=>'Create HostFace', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#host-face-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>
<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id'=>'host-face-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'name',
		array(
                    'type' => 'raw', 
                    'name' =>  'svg'
                ),
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
