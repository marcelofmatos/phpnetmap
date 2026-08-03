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
$('#connection-search form').submit(function(){
	$('#connection-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<div class="card mb-3">
	<div class="card-header p-0">
		<button class="btn btn-link text-decoration-none text-body w-100 text-start d-flex justify-content-between align-items-center px-3 py-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#connection-search" aria-expanded="false" aria-controls="connection-search">
			<span>🔍 Advanced Search</span>
			<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
		</button>
	</div>
	<div class="collapse" id="connection-search">
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
	'id'=>'connection-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
                array(
                    'name' => 'hostSrc',
                    'type' => 'raw',
                    'value' => 'CHtml::link($data->hostSrc->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data->hostSrc->name)), array("class"=>"view host-type ". $data->hostSrc->type))',
                ),
		'host_src_port',
                array(
                    'name' => 'hostDst',
                    'type' => 'raw',
                    'value' => 'CHtml::link($data->hostDst->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data->hostDst->name)), array("class"=>"view host-type ". $data->hostDst->type))',
                ),
		'host_dst_port',
                array(
                    'name' => 'type',
                    'type' => 'raw',
                    'value' => '$data->type',
                ),
            
            
                
                
                
		array(
			'class'=>'CButtonColumn',
			'template' => '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
			'buttons' => array(
				// Só copia o type (cabo) — host_src/dst id/port ficam de
				// fora, copiar um link físico inteiro não faz sentido.
				'copy' => array(
					'label' => 'Copy Connection',
					'imageUrl' => Yii::app()->request->baseUrl.'/images/copy.gif',
					'url' => '$this->grid->controller->createUrl("connection/create", array("type" => $data->type))',
					'options' => array('class' => 'copy'),
				),
			),
		),
	),
)); ?>
