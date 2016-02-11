<?php
/* @var $this ConnectionController */
/* @var $model Connection */

$this->breadcrumbs=array(
	'Connections'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Connection', 'url'=>array('index')),
	array('label'=>'Create Connection', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#connection-grid').yiiGridView('update', {
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
	'id'=>'connection-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
                array(
                    'name' => 'hostSrc',
                    'type' => 'raw',
                    'value' => 'CHtml::link($data[hostSrc]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostSrc]->name)), array("class"=>"view host-type ". $data[hostSrc]->type))',
                ),
		'host_src_port',
                array(
                    'name' => 'hostDst',
                    'type' => 'raw',
                    'value' => 'CHtml::link($data[hostDst]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostDst]->name)), array("class"=>"view host-type ". $data[hostDst]->type))',
                ),
		'host_dst_port',
                array(
                    'name' => 'type',
                    'type' => 'raw',
                    'filter' => $model->getTypes(),
                    'value' => '$data[type]',
                ),
            
            
                
                
                
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
