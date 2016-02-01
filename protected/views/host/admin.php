<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Host', 'url'=>array('index')),
	array('label'=>'Create Host', 'url'=>array('create')),
        array('label'=>'Manage Host Faces', 'url' => array('hostFace/index')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#host-grid').yiiGridView('update', {
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
	'id'=>'host-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'name',
		'type',
		'mac',
		'ip',
                array('name' => 'snmpTemplate', 'value' => '$data->snmpTemplate'),
		array(
                    'class' => 'CButtonColumn',
                    'template' => '{view} {update} {delete}',
                    'buttons' => array(
                        'view' => array(
                            'url' => 'Yii::app()->controller->createUrl("host/viewByName",array("name"=>$data->name))',
                            ),
                    ),
                ),
        ),
));
?>
