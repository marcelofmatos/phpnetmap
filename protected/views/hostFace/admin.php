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
$('#host-face-search form').submit(function(){
	$('#host-face-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<div class="card mb-3">
	<div class="card-header p-0">
		<button class="btn btn-link text-decoration-none text-body w-100 text-start d-flex justify-content-between align-items-center px-3 py-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#host-face-search" aria-expanded="false" aria-controls="host-face-search">
			<span>🔍 Advanced Search</span>
			<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
		</button>
	</div>
	<div class="collapse" id="host-face-search">
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
	'id'=>'host-face-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'name',
		array(
                    'type' => 'raw',
                    'name' =>  'svg',
                    // Sem isso o SVG renderiza no tamanho nativo da
                    // imagem (pode passar de 1000px) e quebra a grade —
                    // limita a largura e deixa a altura proporcional
                    // (ver .host-face-admin-thumb em css/main.css).
                    'value' => '\'<div class="host-face-admin-thumb">\'.$data->svg.\'</div>\'',
                ),
		array(
			'class'=>'CButtonColumn',
			'template' => '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
			'buttons' => array(
				// Só o id vai na URL — o svg (com a foto em base64) é
				// grande demais pra passar por query string.
				'copy' => array(
					'label' => 'Copy HostFace',
					'imageUrl' => Yii::app()->request->baseUrl.'/images/copy.gif',
					'url' => '$this->grid->controller->createUrl("hostFace/create", array("copyFromId" => $data->id))',
					'options' => array('class' => 'copy'),
				),
			),
		),
	),
)); ?>
