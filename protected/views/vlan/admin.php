<?php
/* @var $this VlanController */
/* @var $model Vlan */

$this->breadcrumbs=array(
	'Vlans'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Vlan', 'url'=>array('index')),
	array('label'=>'Create Vlan', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#vlan-grid').yiiGridView('update', {
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
	'id'=>'vlan-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
                array(
                        'name'  => 'tag',
                        'value' => 'CHtml::link($data->tag, Yii::app()->createUrl(
                            "vlan/view",
                            array(
                                "id"=>$data->primaryKey)),
                                array(
                                    "style"=>"color:#$data->font_color; background-color:#$data->background_color",
                                    "class"=>"vlanlabel"
                                )
                            )',
                        'type'  => 'raw',
                    ),
		'name',
		'description',
		array(
			'class'=>'CButtonColumn',
                        'template'=> '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
                        'buttons'=>array(
                            'copy'=>array(
                                'label' => 'Copy VLAN',
                                'imageUrl'=>Yii::app()->request->baseUrl.'/images/copy.gif',
                                'url'=>'$this->grid->controller->createUrl("vlan/create", array("tag" => $data[tag],"name" => $data[name],"font_color" => $data[font_color],"background_color" => $data[background_color]))',
                            ),
                        ),
		),
	),
)); ?>
