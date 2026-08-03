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
$('#vlan-search form').submit(function(){
	$('#vlan-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<div class="card mb-3">
	<div class="card-header p-0">
		<button class="btn btn-link text-decoration-none text-body w-100 text-start d-flex justify-content-between align-items-center px-3 py-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vlan-search" aria-expanded="false" aria-controls="vlan-search">
			<span>🔍 Advanced Search</span>
			<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
		</button>
	</div>
	<div class="collapse" id="vlan-search">
		<div class="card-body">
			<p class="note">
				You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
				or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
			</p>
			<?php $this->renderPartial('_search',array(
				'model'=>$model,
			)); ?>
		</div>
	</div>
</div>

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
                                'url'=>'$this->grid->controller->createUrl("vlan/create", array("tag" => $data->tag,"name" => $data->name,"font_color" => $data->font_color,"background_color" => $data->background_color))',
                                'options' => array('class' => 'copy'),
                            ),
                        ),
		),
	),
)); ?>
